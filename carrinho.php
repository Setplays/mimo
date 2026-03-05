<?php
// carrinho.php
require 'db.php';

// ADICIONAR AO CARRINHO COM DISTRIBUIÇÃO DE SABORES
if (isset($_POST['add_to_cart'])) {
    $id = $_POST['produto_id']; 
    $sabores_nomes = $_POST['sabor_nome'] ?? [];
    $sabores_qtds = $_POST['sabor_qtd'] ?? [];
    $qtd_total = (int)$_POST['quantidade'];
    
    if (!isset($_SESSION['carrinho'])) { $_SESSION['carrinho'] = []; }
    
    $tem_sabores_selecionados = false;
    
    // Se o produto possui sabores
    if (!empty($sabores_nomes)) {
        for ($i = 0; $i < count($sabores_nomes); $i++) {
            $sabor = $sabores_nomes[$i];
            $qtd_sabor = (int)$sabores_qtds[$i];

            // Só adiciona ao carrinho se a quantidade deste sabor for maior que 0
            if ($qtd_sabor > 0) {
                $cart_key = $id . '|' . $sabor;
                if (isset($_SESSION['carrinho'][$cart_key])) {
                    $_SESSION['carrinho'][$cart_key] += $qtd_sabor;
                } else {
                    $_SESSION['carrinho'][$cart_key] = $qtd_sabor;
                }
                $tem_sabores_selecionados = true;
            }
        }
    }

    // Se o produto NÃO TEM sabores vinculados, salva normal pela quantidade total
    if (!$tem_sabores_selecionados) {
        $cart_key = $id . '|';
        if (isset($_SESSION['carrinho'][$cart_key])) {
            $_SESSION['carrinho'][$cart_key] += $qtd_total;
        } else {
            $_SESSION['carrinho'][$cart_key] = $qtd_total;
        }
    }
    
    header("Location: carrinho.php"); 
    exit;
}

// ATUALIZAR QUANTIDADE DIRETO NO CARRINHO
if (isset($_POST['update_cart'])) {
    $cart_key = $_POST['cart_key'];
    $nova_qtd = (int)$_POST['quantidade'];
    if ($nova_qtd > 0) { $_SESSION['carrinho'][$cart_key] = $nova_qtd; } 
    else { unset($_SESSION['carrinho'][$cart_key]); }
    header("Location: carrinho.php"); exit;
}

if (isset($_GET['remover'])) {
    unset($_SESSION['carrinho'][$_GET['remover']]); header("Location: carrinho.php"); exit;
}

$mensagem = '';

if (isset($_POST['finalizar_compra'])) {
    if (!empty($_SESSION['carrinho'])) {
        $nome = $_POST['nome']; $whatsapp = $_POST['whatsapp']; $endereco = $_POST['endereco']; $total_geral = 0; $mp_items = [];

        $stmt_pedido = $db->prepare("INSERT INTO pedidos (cliente_nome, cliente_whatsapp, endereco, total) VALUES (?, ?, ?, 0)");
        $stmt_pedido->execute([$nome, $whatsapp, $endereco]); $pedido_id = $db->lastInsertId();
        $stmt_item = $db->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco, sabor) VALUES (?, ?, ?, ?, ?)");

        foreach ($_SESSION['carrinho'] as $cart_key => $qtd) {
            $parts = explode('|', $cart_key);
            $id = $parts[0]; $sabor = $parts[1] ?? '';
            
            $stmt = $db->prepare("SELECT nome, preco FROM produtos WHERE id = ?"); $stmt->execute([$id]); $p = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_geral += ($p['preco'] * $qtd);
            
            $stmt_item->execute([$pedido_id, $id, $qtd, $p['preco'], $sabor]);
            
            $titulo_mp = $p['nome'] . ($sabor ? " (" . $sabor . ")" : "");
            $mp_items[] = [ "title" => $titulo_mp, "quantity" => (int)$qtd, "unit_price" => (float)$p['preco'] ];
        }
        
        $db->prepare("UPDATE pedidos SET total = ? WHERE id = ?")->execute([$total_geral, $pedido_id]);

        $access_token = 'COLOQUE_SEU_ACCESS_TOKEN_AQUI'; 
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $url_base = $protocolo . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        
        $preference_data = [
            "items" => $mp_items, "payer" => [ "name" => $nome, "phone" => ["number" => $whatsapp] ],
            "back_urls" => [ "success" => $url_base . "/index.php?sucesso=1", "failure" => $url_base . "/carrinho.php?erro=1", "pending" => $url_base . "/index.php?sucesso=1" ],
            "auto_return" => "approved"
        ];

        $ch = curl_init("https://api.mercadopago.com/checkout/preferences");
        curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ "Authorization: Bearer " . $access_token, "Content-Type: application/json" ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); $response = curl_exec($ch); curl_close($ch);
        
        $mp_result = json_decode($response, true);
        
        if (isset($mp_result['init_point'])) {
            $_SESSION['carrinho'] = []; header("Location: " . $mp_result['init_point']); exit;
        } else {
            $mensagem = "Erro de conexão com o Mercado Pago. Nosso administrador entrará em contato."; $_SESSION['carrinho'] = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Carrinho - Mimo d'cacau</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header style="padding-top: 2rem;">
        <div class="top-bar">
            <button class="theme-toggle" onclick="toggleTheme()">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                Tema
            </button>
        </div>
        <img id="logo-img" src="mimod_cacaubranco.png" alt="Mimo d'cacau Logo" class="logo" style="max-width: 150px;">
        <h1 style="margin-top: 10px;">Seu Carrinho</h1>
        <p><a href="catalogo.php"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> Continuar Comprando</a></p>
    </header>

    <div class="container" style="padding-top: 0;">
        <?php if ($mensagem): ?>
            <div class="admin-form" style="text-align: center; color: #8B0000; font-size: 1.2rem; font-weight: bold;"><?= $mensagem ?></div>
            <p style="text-align: center;"><a href="index.php" style="text-decoration: underline;">Voltar à página inicial</a></p>
        <?php endif; ?>

        <?php if (isset($_GET['erro'])): ?>
            <div style="background: #8B0000; color: white; padding: 15px; text-align: center; border-radius: 10px; margin-bottom: 20px;">Seu pagamento não foi concluído. Por favor, tente novamente.</div>
        <?php endif; ?>

        <?php if (empty($_SESSION['carrinho']) && !$mensagem): ?>
            <div class="admin-form" style="text-align: center;"><h2>Seu carrinho está vazio.</h2><br><a href="catalogo.php" class="btn-comprar">Ver Catálogo</a></div>
        <?php elseif (!empty($_SESSION['carrinho'])): ?>
            <div style="overflow-x: auto;">
                <table class="table-carrinho" style="min-width: 600px;">
                    <tr><th>Produto</th><th>Qtd</th><th>Preço Un.</th><th>Subtotal</th><th>Ação</th></tr>
                    <?php 
                    $total_compra = 0;
                    foreach ($_SESSION['carrinho'] as $cart_key => $qtd): 
                        $parts = explode('|', $cart_key); $id = $parts[0]; $sabor = $parts[1] ?? '';
                        $stmt = $db->prepare("SELECT nome, preco FROM produtos WHERE id = ?"); $stmt->execute([$id]); $p = $stmt->fetch(PDO::FETCH_ASSOC);
                        $subtotal = $p['preco'] * $qtd; $total_compra += $subtotal;
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($p['nome']) ?></strong>
                            <?php if($sabor): ?><br><small style="opacity: 0.8; font-weight: bold; color: var(--text-color);">📍 Sabor: <?= htmlspecialchars($sabor) ?></small><?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: flex; align-items: center; margin: 0;">
                                <input type="hidden" name="update_cart" value="1">
                                <input type="hidden" name="cart_key" value="<?= htmlspecialchars($cart_key) ?>">
                                <input type="number" name="quantidade" value="<?= $qtd ?>" min="0" style="width: 70px; padding: 5px 10px; border-radius: 8px; border: 1px solid var(--text-color); background: var(--bg-color); color: var(--text-color); font-size: 1rem;" onchange="this.form.submit()">
                            </form>
                        </td>
                        <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                        <td><a href="carrinho.php?remover=<?= urlencode($cart_key) ?>" style="color: #D32F2F; font-weight: bold; display: flex; align-items: center; gap: 5px;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Remover</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: bold; font-size: 1.2rem;">TOTAL:</td>
                        <td colspan="2" style="font-weight: bold; font-size: 1.3rem; color: #25D366;">R$ <?= number_format($total_compra, 2, ',', '.') ?></td>
                    </tr>
                </table>
            </div>

            <div class="admin-form" style="max-width: 600px; margin: 40px auto; background-color: var(--card-bg);">
                <h2 style="margin-bottom: 20px; text-align: center;">Detalhes da Entrega</h2>
                <form method="POST">
                    <label>Seu Nome Completo:</label><input type="text" name="nome" required>
                    <label>WhatsApp (Para combinarmos a entrega):</label><input type="text" name="whatsapp" required placeholder="(00) 00000-0000">
                    <label>Endereço de Entrega (ou "Retirar na loja"):</label><textarea name="endereco" rows="3" required></textarea>
                    
                    <div style="background: rgba(0,0,0,0.05); padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center;">
                        <button type="submit" name="finalizar_compra" style="width: 100%; font-size: 1.2rem; background-color: #009EE3; color: white; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            Pagar com Mercado Pago
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <script>
        function aplicarTema() {
            const isDark = localStorage.getItem('theme') === 'dark';
            const body = document.body;
            const logo = document.getElementById('logo-img');
            if (isDark) { body.setAttribute('data-theme', 'dark'); if (logo) logo.src = "mimod_cacau.png"; } 
            else { body.removeAttribute('data-theme'); if (logo) logo.src = "mimod_cacaubranco.png"; }
        }
        function toggleTheme() {
            const isDark = document.body.getAttribute('data-theme') === 'dark';
            localStorage.setItem('theme', isDark ? 'light' : 'dark');
            aplicarTema();
        }
        aplicarTema();
    </script>
</body>
</html>