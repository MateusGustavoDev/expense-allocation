# Rateio de despesas

Sistema para ratear despesas compartilhadas entre unidades de empresas de um mesmo grupo. Substitui a planilha: cadastro de empresas, unidades e despesas com rateio percentual, conversão de dólar para real pela cotação da data da despesa, importação por CSV com relatório de erros e relatório de total em reais por unidade num período, tanto na API REST quanto na interface.

**Stack:** Laravel 13 · PHP 8.4 · MySQL 8.4 · Blade + Livewire 4 · Tailwind CSS 4 · Pest · Docker

- **Produção:** https://expense-allocation.up.railway.app
- **Documentação da API (OpenAPI):** https://expense-allocation.up.railway.app/docs/api

As credenciais de acesso à produção foram enviadas junto com a entrega. Localmente, o seeder cria um usuário de desenvolvimento (veja abaixo).

---

## Como rodar

Só é preciso ter o **Docker** instalado: PHP, Composer, Node e MySQL rodam em containers.

```bash
git clone https://github.com/MateusGustavoDev/expense-allocation.git
cd expense-allocation
cp .env.example .env

docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Acesse **http://localhost:8000** e entre com `admin@example.com` / `password`. Esse usuário só é criado no ambiente local.

Para ver a interface preenchida, carregue os dados de demonstração: um grupo com 3 empresas, 5 unidades e seis meses de despesas realistas (aluguéis, SaaS em dólar, contas, eventuais), rateadas pelo número de funcionários de cada unidade. As despesas passam pela mesma regra da API, e as em dólar são convertidas pelo worker com a cotação real de cada data.

```bash
docker compose exec app php artisan db:seed --class=DemoSeeder
```

Na primeira subida, o container `vite` instala as dependências do Node antes de servir os assets: se a página abrir sem estilo, aguarde alguns segundos e recarregue.

| Serviço | Papel | Porta |
| --- | --- | --- |
| `app` | Nginx + PHP-FPM | 8000 |
| `queue` | Worker da fila (`queue:work`): conversão de moeda | — |
| `scheduler` | Tarefas agendadas (`schedule:work`): reprocessa conversões pendentes a cada 10 min | — |
| `vite` | Assets em desenvolvimento | 5173 |
| `mysql` | MySQL 8.4; também cria o banco `testing` usado pelos testes | 3307 |

### Testes e qualidade

```bash
docker compose exec app composer test          # Pest, no MySQL (mesmo banco de produção)
docker compose exec app composer format:check  # Pint
docker compose exec app composer analyse       # Larastan, nível 8
```

Os mesmos três comandos rodam no CI em todo pull request.

### Roteiro rápido

1. Em **Cadastros**, crie uma empresa e as unidades com os slugs `unidade-a`, `unidade-b` e `unidade-c`. O slug identifica a unidade no CSV.
2. Em **Importar CSV**, envie `docs/examples/despesas-setembro.csv`. O arquivo tem três linhas inválidas de propósito (data inexistente, rateio somando 90% e unidade desconhecida): as outras dez entram, e o relatório aponta cada erro pelo número da linha.
3. Em **Despesas**, as despesas em dólar aparecem como pendentes e o worker as converte em segundos. **Nova despesa** mostra a divisão dos centavos enquanto você digita os percentuais.

   > Uma despesa em dólar **com a data de hoje** fica pendente até o dia seguinte: a cotação PTAX do dia só é definitiva depois que ele termina (veja [Conversão de moeda](#conversão-de-moeda)). Para ver a conversão na hora, use uma data passada.
4. Em **Relatório**, escolha o período e veja o total em reais por unidade.

### Usando a API

```bash
# 1. Troque e-mail e senha por um token
curl -s http://localhost:8000/api/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"password","device_name":"curl"}'

# 2. Use o token nas demais rotas
curl -s 'http://localhost:8000/api/reports/unit-totals?date_from=2026-09-01&date_to=2026-09-30' \
  -H 'Accept: application/json' -H 'Authorization: Bearer <token>'
```

Todos os endpoints, com parâmetros, validações e formato de resposta, estão em **http://localhost:8000/docs/api**. No painel "Try It" de cada rota, informe o token para testar direto pela página.

Staging e produção não têm cadastro público: usuários são criados com `php artisan users:create`, que pede a senha sem mostrá-la no terminal.

---

## Decisões

### Dinheiro e rateio

- **Valores em centavos (inteiro) e percentuais em pontos-base (10000 = 100%).** Nunca float: `0.1 + 0.2` não é `0.3`. A conversão entre texto decimal e centavos acontece só na borda (requisição, CSV, resposta), por uma função única e testada (`Decimal`).
- **Rateio pelo método do maior resto** (`AllocationSplitter`): calcula a parte inteira de cada unidade e distribui os centavos que sobram, um a um, para as maiores frações; o empate é resolvido pela ordem de entrada. A soma das partes é sempre igual ao total: R$ 100,00 em 3 × 33,33% vira R$ 33,34 + R$ 33,33 + R$ 33,33.
- **O rateio é calculado sobre o valor original e de novo sobre o valor convertido**, para o relatório em reais também fechar centavo a centavo.
- **A soma de 100% é validada duas vezes**: no Form Request e na Action, que também é chamada pelo import de CSV.

### Conversão de moeda

- **Cotação PTAX do Banco Central, de venda.** É a referência oficial, pública e sem chave de acesso. Fica atrás da interface `ExchangeRateProvider`: trocar de provedor é trocar um binding.
- **A despesa em dólar é salva primeiro, como pendente, e a conversão vai para a fila.** Se a API do Banco Central estiver fora do ar, o cadastro acontece do mesmo jeito; é isso que garante que a despesa não se perde.
- **Nova tentativa automática:** 5 tentativas com espera crescente (1 min, 5 min, 15 min, 1 h). Esgotadas, a despesa fica como "falhou" e pode ser reenviada pela interface ou pela API. O `scheduler` ainda reenfileira as pendentes a cada 10 minutos, como rede de segurança.
- **Cotação da data da despesa, usada depois que o dia fecha no horário de Brasília.** Durante o dia a PTAX ainda não é definitiva, então a despesa com a data de hoje fica pendente e o `scheduler` a converte no dia seguinte. A alternativa comum no mercado é usar a PTAX do dia útil anterior, já conhecida no momento do lançamento; segui o enunciado ao pé da letra, e a escolha está entre as perguntas ao negócio. Sábado, domingo e feriado usam o último dia útil anterior; a data efetivamente usada fica registrada na despesa.
- **Cotações ficam guardadas no banco:** a API externa é consultada no máximo uma vez por data.
- **Multiplicação com bcmath:** centavos × cotação estouraria um inteiro de 64 bits, e float perderia precisão.

### Importação e relatório

- **Cada linha do CSV é validada e gravada na própria transação**, com as mesmas regras da criação pela API. Uma linha inválida nunca derruba as outras, e o relatório aponta o erro pelo número da linha no arquivo.
- **O leitor tolera arquivos do mundo real:** BOM UTF-8, quebras `\r\n`, linhas em branco e arquivos em Windows-1252, a codificação que o Excel usa no Brasil.
- **O relatório soma no banco** (`SUM ... GROUP BY`), não em PHP, e considera só despesas já convertidas. Pendentes e falhas do período aparecem à parte, para deixar claro quando o total está incompleto.

### Sobre CI/CD, ambientes e design system

Sei que um projeto deste tamanho provavelmente não precisaria de pipeline de CI/CD, ambientes separados de staging e produção ou de um design system próprio. Incluí esses elementos de propósito, para mostrar alguns padrões e práticas que gosto de adotar em projetos que vão crescer: cada mudança passa por checks automatizados antes do merge, é validada em staging antes de chegar à produção, e a interface segue componentes consistentes em vez de estilos soltos em cada tela.

- **Fluxo:** `feature/*` → PR → `staging` → PR → `main`. Push direto é bloqueado nas duas branches, e os PRs são mesclados com merge commit, preservando o histórico.
- **CI** (GitHub Actions) em todo PR e em todo push para `staging` e `main`: formatação, análise estática, build dos assets e testes.
- **Deploy no Railway:** cada ambiente observa sua branch e só publica commits com o CI verde. Migrations rodam antes de a nova versão entrar no ar, e a troca só acontece se o healthcheck responder. `web`, `worker` e `scheduler` rodam a mesma imagem.
- **Design system:** componentes Blade em `resources/views/components/ui` e tokens de cor no Tailwind. O catálogo com todas as variantes fica em `/ui` (só no ambiente local).

---

## O que ficou de fora, e por quê

- **Edição e exclusão de despesas.** Editar uma despesa já convertida e rateada levanta perguntas de negócio (reconverter? manter histórico?), listadas abaixo. Preferi não decidir isso sozinho.
- **Testes E2E no navegador.** As regras estão cobertas por testes unitários e de feature, e as telas por testes de componente Livewire. Os fluxos de interface foram verificados manualmente no navegador; automatizá-los seria o próximo passo.
- **Menu para telas pequenas.** A interface foi pensada para desktop; abaixo de 1024px a navegação lateral não aparece.
- **Importação assíncrona.** O CSV é processado dentro da requisição, com limite de 5 MB. Arquivos grandes iriam para um job, com o relatório consultado depois.
- **Proteção contra importação duplicada.** Enviar o mesmo arquivo duas vezes cria as despesas duas vezes. A regra certa depende do negócio (ver perguntas).
- **Perfis de acesso e gestão de usuários pela interface.** Todo usuário autenticado pode tudo; usuários são criados por comando.
- **Informar a cotação manualmente.** Se o Banco Central não tiver cotação para a data, a despesa fica como "falhou" até ser reprocessada; não há como digitar a cotação na mão.

---

## Perguntas ao time de negócio

**Câmbio**
1. A cotação de referência é a PTAX de venda do Banco Central? Algumas empresas usam a de compra, a do dia do pagamento ou a da fatura do cartão.
2. Despesa com data de sábado, domingo ou feriado usa a cotação do último dia útil anterior. É isso, ou deveria ser o próximo dia útil?
3. Despesa com a data de hoje fica pendente até o fim do dia, porque a PTAX só fecha à tarde. Serve, ou é preciso um valor provisório?
4. Quando a conversão falha de vez, alguém pode informar a cotação manualmente? Quem?
5. Outras moedas além de dólar serão necessárias (euro, por exemplo)?

**Rateio e despesas**

6. Uma despesa pode ser editada depois de rateada e convertida? Se o valor ou o rateio mudar, os relatórios de períodos já fechados podem mudar também?
7. Excluir uma despesa deve apagá-la ou apenas cancelá-la, mantendo o histórico?
8. Percentuais com duas casas decimais são suficientes? Há casos de rateio por valor fixo em vez de percentual?
9. Uma unidade que deixa de existir deve ser excluída ou desativada? Hoje a exclusão é bloqueada enquanto ela tiver despesas.

**Importação e relatório**

10. Como tratar uma linha do CSV que repete uma despesa já cadastrada (mesma data, fornecedor e valor)? Ignorar, avisar ou importar mesmo assim?
11. O relatório por período usa a data da despesa. Seria a data de competência, a de pagamento ou a de vencimento?
12. O relatório precisa de fechamento mensal, ou seja, um período que depois de fechado não muda mais?

**Acesso**

13. Todos os usuários podem cadastrar, importar e ver tudo? Ou cada pessoa só enxerga as unidades da própria empresa?
14. Quem cria e desativa usuários? Precisa de recuperação de senha por e-mail ou login com a conta Google ou Microsoft da empresa?
