# Atlas de Países

Aplicação web desenvolvida em **PHP** que consulta uma API pública, recebe os dados em **JSON**, processa e apresenta as informações de forma dinâmica ao usuário.

O usuário digita o nome de um país, o PHP faz a requisição HTTP à API **REST Countries** e a página exibe bandeira, capital, população, moeda, região e idiomas.

## Tema da atividade

**API 4 - REST Countries**: fornece informações sobre países, como capital, população, moeda e região.
Site da API: https://restcountries.com

## Como funciona

1. O usuário digita o nome de um país (em português ou inglês) e clica em **Consultar**.
2. O PHP faz uma requisição `GET` à API REST Countries **v5** (`https://api.restcountries.com/countries/v5`), enviando a chave de acesso no cabeçalho `Authorization: Bearer`.
3. A busca tenta, nesta ordem:
   - nome exato (ex.: `Brazil`);
   - parte do nome (ex.: `Braz`);
   - tradução do nome (ex.: `Noruega`, `Reino Unido`).
4. A API retorna um JSON. O PHP usa `json_decode`, escolhe o resultado mais parecido com o que foi digitado e monta a página com os dados.

Exemplo de resultado para "Brasil":

```
Nome:       Brasil
Capital:    Brasília
População:  (valor retornado pela API)
Moeda:      Brazilian real (BRL)
Região:     Américas
Idiomas:    Portuguese
```

## Funcionalidades

- Página inicial com título, formulário, campo de pesquisa, botão de consulta e área de resultados
- Consulta à API feita pelo PHP, no servidor
- Pesquisa por nome em português ou inglês
- Exibição da bandeira, nome (em português), nome oficial, capital, população, moeda, região e idiomas
- Mensagens de erro claras: campo vazio, país não encontrado, chave inválida, limite de consultas e falha de conexão
- Validação do campo e indicador de carregamento no botão (JavaScript)
- Layout responsivo, adaptado para celular

## Tecnologias

- PHP 7.4 ou superior
- HTML5 e CSS3
- JavaScript (apenas para melhorar a interface)
- API REST Countries v5

## Como executar

### Requisitos

- PHP instalado (ou XAMPP)
- Uma chave gratuita da API: https://restcountries.com/sign-up

### Passo a passo

1. Baixe ou clone este repositório:

   ```bash
   git clone <link-do-repositorio>
   ```

2. Copie o arquivo `config.example.php` para `config.php`.
3. Abra o `config.php` e coloque a sua chave no lugar de `rc_live_demo`:

   ```php
   define("RESTCOUNTRIES_API_KEY", "SUA_CHAVE_AQUI");
   ```

   O `config.php` está no `.gitignore` e não é enviado ao GitHub. Se ele não existir, o sistema usa a chave de demonstração do `config.example.php`, que retorna apenas dados de amostra.

4. Execute de uma destas formas:

   **Com o XAMPP**
   - Copie a pasta do projeto para `C:\xampp\htdocs\`.
   - Inicie o **Apache** no painel do XAMPP.
   - Acesse `http://localhost/nome-da-pasta/` no navegador.

   **Com o servidor embutido do PHP**
   ```bash
   cd nome-da-pasta
   php -S localhost:8000
   ```
   Acesse `http://localhost:8000`.

Observação: o PHP precisa estar com `allow_url_fopen` e a extensão `openssl` ativos (já vêm ativos no XAMPP).

## Estrutura do projeto

```
atlas-de-paises/
├── index.php           # formulário, consulta à API em PHP e exibição dos resultados
├── style.css           # estilos da página
├── script.js           # validação do campo e botão de carregamento
├── config.example.php  # modelo do arquivo de configuração (chave da API)
├── .gitignore          # impede o envio do config.php com a chave pessoal
└── README.md
```

## Observação sobre a API

As versões antigas da REST Countries (v1 a v4, incluindo a `v3.1` muito citada em tutoriais) foram desativadas. Este projeto usa a **v5**, que exige uma chave de API e devolve o JSON em outro formato (os países ficam em `data.objects`).

## Foto de demonstração

<img width="1346" height="639" alt="image" src="https://github.com/user-attachments/assets/71051413-cd17-43da-817a-506e51929ec0" />

## Autor

Feito por: Melissa Martins
