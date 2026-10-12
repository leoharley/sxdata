<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#23345F">
    <title><?= html_escape($page_title ?? 'Pesquisa') ?> - SXData</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sx-verde: #8fae5d;
            --sx-verde-escuro: #5a7536;
            --sx-azul: #23345F;
        }
        body {
            background: #f3f5f2;
            color: #1f2937;
            min-height: 100vh;
        }
        .pub-topo {
            background: linear-gradient(135deg, #8fae5d, #a8c46a);
            padding: 18px 16px 52px;
            text-align: center;
        }
        .pub-topo img { width: 150px; max-width: 45%; height: auto; }
        .pub-cartao {
            max-width: 720px;
            margin: -36px auto 32px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 6px 24px rgba(35, 52, 95, 0.10);
            padding: 24px 20px;
        }
        @media (min-width: 576px) {
            .pub-cartao { padding: 32px; margin-left: auto; margin-right: auto; }
        }
        .pub-cartao h1 {
            font-size: 1.35rem;
            color: var(--sx-azul);
            font-weight: 700;
        }
        .pub-rodape {
            text-align: center;
            color: #6b7280;
            font-size: .8rem;
            padding: 0 16px 28px;
        }
        .btn-sx {
            background: var(--sx-verde);
            border-color: var(--sx-verde);
            color: #fff;
            font-weight: 600;
        }
        .btn-sx:hover, .btn-sx:focus { background: var(--sx-verde-escuro); border-color: var(--sx-verde-escuro); color: #fff; }
    </style>
</head>
<body>
    <header class="pub-topo">
        <img src="<?= img_url('logo_azul_com_branco.png') ?>" alt="SocialX">
    </header>
    <main class="px-3">
        <div class="pub-cartao">
