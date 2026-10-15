<?php

$arquivoConfig = file_exists(__DIR__ . "/config.php") ? "config.php" : "config.example.php";
require __DIR__ . "/" . $arquivoConfig;

$pais = "";
$paises = [];
$erro = "";

function consultarApi($caminho)
{
    $url = "https://api.restcountries.com/countries/v5" . $caminho;

    $contexto = stream_context_create([
        "http" => [
            "method"        => "GET",
            "header"        => "Authorization: Bearer " . RESTCOUNTRIES_API_KEY . "\r\n" .
                               "Accept: application/json\r\n" .
                               "User-Agent: Mozilla/5.0 (AtlasDePaises)\r\n",
            "timeout"       => 15,
            "ignore_errors" => true
        ]
    ]);

    $resposta = @file_get_contents($url, false, $contexto);

    if ($resposta === false) {
        throw new Exception("Não foi possível conectar à API. Verifique a internet e se allow_url_fopen e openssl estão ativos no php.ini.");
    }

    $status = 0;
    foreach ($http_response_header as $linha) {
        if (preg_match("/^HTTP\/\S+\s(\d{3})/", $linha, $m)) {
            $status = (int)$m[1];
        }
    }

    if ($status === 401) {
        throw new Exception("Chave da API ausente ou inválida. Confira a chave no arquivo config.php.");
    }
    if ($status === 403) {
        throw new Exception("Acesso negado pela API (a conta pode ter excedido o limite mensal de consultas).");
    }
    if ($status === 429) {
        throw new Exception("Muitas consultas em pouco tempo. Aguarde alguns segundos e tente de novo.");
    }
    if ($status === 404) {
        return null;
    }
    if ($status !== 200) {
        throw new Exception("A API respondeu com o status HTTP {$status}.");
    }

    $dados = json_decode($resposta, true);
    if (!is_array($dados)) {
        throw new Exception("A resposta da API não está em um formato JSON válido.");
    }

    $objetos = $dados["data"]["objects"] ?? [];
    return $objetos ?: null;
}

function formatarMoedas($moedas)
{
    $lista = [];
    foreach ((array)$moedas as $chave => $m) {
        if (is_array($m)) {
            $nome   = $m["name"] ?? "";
            $codigo = $m["code"] ?? (is_string($chave) ? $chave : "");
        } else {
            $nome   = (string)$m;
            $codigo = is_string($chave) ? $chave : "";
        }
        $texto = trim($nome . ($codigo !== "" && $nome !== "" ? " ({$codigo})" : $codigo));
        if ($texto !== "") {
            $lista[] = $texto;
        }
    }
    return $lista ? implode(", ", $lista) : "Não informada";
}

function formatarIdiomas($idiomas)
{
    $lista = [];
    foreach ((array)$idiomas as $i) {
        if (is_array($i)) {
            $nome = $i["name"] ?? $i["english_name"] ?? $i["english"] ?? "";
            if ($nome === "") {
                foreach ($i as $valor) {
                    if (is_string($valor) && $valor !== "") { $nome = $valor; break; }
                }
            }
        } else {
            $nome = (string)$i;
        }
        if ($nome !== "") {
            $lista[] = $nome;
        }
    }
    return $lista ? implode(", ", $lista) : "Não informado";
}

function formatarCapitais($capitais)
{
    $lista = [];
    foreach ((array)$capitais as $c) {
        $nome = is_array($c) ? ($c["name"] ?? "") : (string)$c;
        if ($nome !== "") {
            $lista[] = $nome;
        }
    }
    return $lista ? implode(", ", $lista) : "Não informada";
}

function ordenarPorRelevancia($lista, $busca)
{
    $busca = mb_strtolower(trim($busca));

    $nota = function ($p) use ($busca) {
        $nomes = [
            mb_strtolower($p["names"]["translations"]["por"]["common"] ?? ""),
            mb_strtolower($p["names"]["common"] ?? "")
        ];
        foreach ($nomes as $n) {
            if ($n === $busca) { return 0; }
        }
        foreach ($nomes as $n) {
            if ($n !== "" && strpos($n, $busca) === 0) { return 1; }
        }
        return 2;
    };

    $exatos = array_values(array_filter($lista, fn($p) => $nota($p) === 0));
    if ($exatos) {
        return $exatos;
    }

    usort($lista, fn($a, $b) => $nota($a) <=> $nota($b));
    return array_slice($lista, 0, 6);
}

$regioes = [
    "Africa"    => "África",
    "Americas"  => "Américas",
    "Asia"      => "Ásia",
    "Europe"    => "Europa",
    "Oceania"   => "Oceania",
    "Antarctic" => "Antártica"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $pais = trim($_POST["pais"] ?? "");

    if ($pais === "") {
        $erro = "Digite o nome de um país!";
    } else {
        try {
            $campos = "limit=25&response_fields=names,capitals,population,currencies,region,languages,flag";
            $busca = rawurlencode($pais);

            $tentativas = [
                "/names.common/{$busca}?{$campos}",
                "/name?q={$busca}&{$campos}",
                "/names.translations?q={$busca}&{$campos}"
            ];

            foreach ($tentativas as $caminho) {
                $paises = consultarApi($caminho);
                if ($paises) {
                    break;
                }
            }

            if (!$paises) {
                $paises = [];
                $erro = "País não encontrado!";
            } else {
                $paises = ordenarPorRelevancia($paises, $pais);
            }
        } catch (Exception $e) {
            $paises = [];
            $erro = $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atlas de Países</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header class="topo">
        <div class="topo-conteudo">
            <h1>Atlas de Países</h1>
            <p class="subtitulo">Pesquise um país e veja capital, população, moeda, região e idiomas.</p>

            <form method="POST" action="index.php" class="busca" id="formulario">
                <label for="pais" class="oculto">Nome do país</label>
                <input
                    type="text"
                    id="pais"
                    name="pais"
                    placeholder="Brasil, Japão, Canada..."
                    value="<?= htmlspecialchars($pais) ?>"
                    autocomplete="off"
                    autofocus
                >
                <button type="submit">Consultar</button>
            </form>
        </div>
    </header>

    <main class="principal">

        <!-- Área de resultados. -->
        <section id="resultado" aria-live="polite">

            <?php if ($erro): ?>
                <p class="aviso erro"><?= htmlspecialchars($erro) ?></p>
            <?php elseif (!$paises): ?>
                <p class="aviso">Digite o nome de um país, em português ou inglês, e clique em Consultar.</p>
            <?php endif; ?>

            <?php foreach ($paises as $p):
                $nome    = $p["names"]["translations"]["por"]["common"]   ?? $p["names"]["common"]   ?? "Sem nome";
                $oficial = $p["names"]["translations"]["por"]["official"] ?? $p["names"]["official"] ?? "";
                $bandeira = $p["flag"]["url_png"] ?? $p["flag"]["url_svg"] ?? "";
                $regiao   = $p["region"] ?? "";
            ?>
                <article class="pais">

                    <?php if ($bandeira): ?>
                        <img class="bandeira" src="<?= htmlspecialchars($bandeira) ?>"
                             alt="Bandeira de <?= htmlspecialchars($nome) ?>">
                    <?php endif; ?>

                    <div class="dados">
                        <h2><?= htmlspecialchars($nome) ?></h2>
                        <p class="oficial"><?= htmlspecialchars($oficial) ?></p>

                        <dl>
                            <div>
                                <dt>Capital</dt>
                                <dd><?= htmlspecialchars(formatarCapitais($p["capitals"] ?? [])) ?></dd>
                            </div>
                            <div>
                                <dt>População</dt>
                                <dd><?= number_format((float)($p["population"] ?? 0), 0, ",", ".") ?> habitantes</dd>
                            </div>
                            <div>
                                <dt>Moeda</dt>
                                <dd><?= htmlspecialchars(formatarMoedas($p["currencies"] ?? [])) ?></dd>
                            </div>
                            <div>
                                <dt>Região</dt>
                                <dd><?= htmlspecialchars($regioes[$regiao] ?? ($regiao ?: "Não informada")) ?></dd>
                            </div>
                            <div class="largo">
                                <dt>Idiomas</dt>
                                <dd><?= htmlspecialchars(formatarIdiomas($p["languages"] ?? [])) ?></dd>
                            </div>
                        </dl>
                    </div>

                </article>
            <?php endforeach; ?>

        </section>

    </main>

    <footer class="rodape">
        <p>Dados fornecidos pela API pública REST Countries.</p>
    </footer>

    <script src="script.js"></script>

</body>

</html>