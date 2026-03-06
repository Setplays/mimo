<?php
// admin.php
require 'db.php';

$error = ''; $success = '';

if (isset($_POST['login'])) {
    if ($_POST['user'] === 'lari' && $_POST['pass'] === '') { $_SESSION['logged_in'] = true; } 
    else { $error = "Usuário ou senha incorretos."; }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: admin.php"); exit; }
$is_logged = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

$aba = $_GET['aba'] ?? 'pedidos';

if ($is_logged && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_sabor'])) {
        $nome = $_POST['nome_sabor']; $imagem = $_FILES['imagem_sabor'];
        if ($imagem['error'] == 0) {
            $nome_arquivo = uniqid() . '.' . pathinfo($imagem['name'], PATHINFO_EXTENSION);
            move_uploaded_file($imagem['tmp_name'], 'uploads/' . $nome_arquivo);
            $db->prepare("INSERT INTO sabores (nome, imagem) VALUES (?, ?)")->execute([$nome, $nome_arquivo]);
            $success = "Sabor cadastrado!";
        }
    }
    if (isset($_POST['update_sabor'])) {
        $id = $_POST['sabor_id']; $nome = $_POST['nome_sabor']; $imagem = $_FILES['imagem_sabor'];
        if ($imagem['error'] == 0) {
            $stmt = $db->prepare("SELECT imagem FROM sabores WHERE id = ?"); $stmt->execute([$id]);
            $sabor_antigo = $stmt->fetch();
            if ($sabor_antigo && file_exists('uploads/' . $sabor_antigo['imagem'])) { @unlink('uploads/' . $sabor_antigo['imagem']); }
            $nome_arquivo = uniqid() . '.' . pathinfo($imagem['name'], PATHINFO_EXTENSION);
            move_uploaded_file($imagem['tmp_name'], 'uploads/' . $nome_arquivo);
            $db->prepare("UPDATE sabores SET nome = ?, imagem = ? WHERE id = ?")->execute([$nome, $nome_arquivo, $id]);
        } else {
            $db->prepare("UPDATE sabores SET nome = ? WHERE id = ?")->execute([$nome, $id]);
        }
        $success = "Sabor atualizado com sucesso!"; header("Refresh: 1; url=admin.php?aba=gerenciar");
    }
    if (isset($_POST['delete_sabor'])) {
        $id = $_POST['sabor_id'];
        $stmt = $db->prepare("SELECT imagem FROM sabores WHERE id = ?"); $stmt->execute([$id]);
        $s = $stmt->fetch(); if ($s) { @unlink('uploads/' . $s['imagem']); }
        $db->prepare("DELETE FROM sabores WHERE id = ?")->execute([$id]);
        $db->prepare("DELETE FROM produto_sabor WHERE sabor_id = ?")->execute([$id]);
        $success = "Sabor deletado!";
    }
    if (isset($_POST['add_produto'])) {
        $nome = $_POST['nome_produto']; $descricao = $_POST['descricao_produto']; $preco = $_POST['preco_produto']; 
        $db->prepare("INSERT INTO produtos (nome, descricao, preco, imagem) VALUES (?, ?, ?, '')")->execute([$nome, $descricao, $preco]);
        $produto_id = $db->lastInsertId();
        $imagens = $_FILES['imagens_produto']; $primeira_imagem = null;
        if (!empty($imagens['name'][0])) {
            foreach ($imagens['name'] as $key => $name) {
                if ($imagens['error'][$key] == 0) {
                    $nome_arquivo = uniqid() . '_' . $key . '.' . pathinfo($name, PATHINFO_EXTENSION);
                    move_uploaded_file($imagens['tmp_name'][$key], 'uploads/' . $nome_arquivo);
                    $db->prepare("INSERT INTO produto_imagens (produto_id, imagem) VALUES (?, ?)")->execute([$produto_id, $nome_arquivo]);
                    if (!$primeira_imagem) { $primeira_imagem = $nome_arquivo; }
                }
            }
        }
        if ($primeira_imagem) { $db->prepare("UPDATE produtos SET imagem = ? WHERE id = ?")->execute([$primeira_imagem, $produto_id]); }
        if (isset($_POST['sabores_selecionados'])) {
            $stmt_rel = $db->prepare("INSERT INTO produto_sabor (produto_id, sabor_id) VALUES (?, ?)");
            foreach ($_POST['sabores_selecionados'] as $sabor_id) { $stmt_rel->execute([$produto_id, $sabor_id]); }
        }
        $success = "Produto cadastrado com sucesso!";
    }
    if (isset($_POST['update_produto'])) {
        $id = $_POST['produto_id']; $nome = $_POST['nome_produto']; $descricao = $_POST['descricao_produto']; $preco = $_POST['preco_produto']; 
        $db->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ? WHERE id = ?")->execute([$nome, $descricao, $preco, $id]);
        $db->prepare("DELETE FROM produto_sabor WHERE produto_id = ?")->execute([$id]);
        if (isset($_POST['sabores_selecionados'])) {
            $stmt_rel = $db->prepare("INSERT INTO produto_sabor (produto_id, sabor_id) VALUES (?, ?)");
            foreach ($_POST['sabores_selecionados'] as $sabor_id) { $stmt_rel->execute([$id, $sabor_id]); }
        }
        $imagens = $_FILES['imagens_produto'];
        if (!empty($imagens['name'][0]) && $imagens['error'][0] == 0) {
            $stmt_img = $db->prepare("SELECT imagem FROM produto_imagens WHERE produto_id = ?"); $stmt_img->execute([$id]);
            foreach($stmt_img->fetchAll() as $img) { @unlink('uploads/' . $img['imagem']); }
            $db->prepare("DELETE FROM produto_imagens WHERE produto_id = ?")->execute([$id]);
            $primeira_imagem = null;
            foreach ($imagens['name'] as $key => $name) {
                if ($imagens['error'][$key] == 0) {
                    $nome_arquivo = uniqid() . '_' . $key . '.' . pathinfo($name, PATHINFO_EXTENSION);
                    move_uploaded_file($imagens['tmp_name'][$key], 'uploads/' . $nome_arquivo);
                    $db->prepare("INSERT INTO produto_imagens (produto_id, imagem) VALUES (?, ?)")->execute([$id, $nome_arquivo]);
                    if (!$primeira_imagem) { $primeira_imagem = $nome_arquivo; }
                }
            }
            if ($primeira_imagem) { $db->prepare("UPDATE produtos SET imagem = ? WHERE id = ?")->execute([$primeira_imagem, $id]); }
        }
        $success = "Produto atualizado com sucesso!"; header("Refresh: 1; url=admin.php?aba=gerenciar");
    }
    if (isset($_POST['delete_produto'])) {
        $id = $_POST['produto_id'];
        $stmt_img = $db->prepare("SELECT imagem FROM produto_imagens WHERE produto_id = ?"); $stmt_img->execute([$id]);
        foreach($stmt_img->fetchAll() as $img) { @unlink('uploads/' . $img['imagem']); }
        $db->prepare("DELETE FROM produto_imagens WHERE produto_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM produtos WHERE id = ?")->execute([$id]);
        $db->prepare("DELETE FROM produto_sabor WHERE produto_id = ?")->execute([$id]);
        $success = "Produto deletado!";
    }
    if (isset($_POST['concluir_pedido'])) {
        $db->prepare("UPDATE pedidos SET status = 'Concluído' WHERE id = ?")->execute([$_POST['pedido_id']]);
        $success = "Pedido marcado como concluído!";
    }
}

$pedidos = $is_logged ? $db->query("SELECT * FROM pedidos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
$sabores_existentes = [];
$produtos_existentes = [];

if ($is_logged) {
    $busca_sabor = $_GET['busca_sabor'] ?? '';
    if ($busca_sabor) {
        $stmt = $db->prepare("SELECT * FROM sabores WHERE nome LIKE :busca ORDER BY id DESC"); $stmt->execute(['busca' => "%$busca_sabor%"]); $sabores_existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else { $sabores_existentes = $db->query("SELECT * FROM sabores ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC); }

    $busca_admin = $_GET['busca_admin'] ?? '';
    if ($busca_admin) {
        $stmt = $db->prepare("SELECT * FROM produtos WHERE nome LIKE :busca ORDER BY id DESC"); $stmt->execute(['busca' => "%$busca_admin%"]); $produtos_existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else { $produtos_existentes = $db->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC); }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Admin - Mimo d'cacau</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .list-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .list-table th, .list-table td { border: 1px solid var(--text-color); padding: 12px 8px; text-align: left; vertical-align: middle; }
        .btn-action { padding: 8px 12px; cursor: pointer; border: 1px solid var(--text-color); background-color: transparent; color: var(--text-color); font-size: 0.85em; border-radius: 5px; text-transform: uppercase; font-weight: bold; margin-right: 5px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s ease;}
        .btn-action:hover { background-color: var(--text-color); color: var(--bg-color); }
        .btn-edit { text-decoration: none; }
        .btn-success { background: var(--text-color); color: var(--bg-color); }
        .btn-success:hover { opacity: 0.8; }
        .aba-nav { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid var(--text-color); padding-bottom: 10px; flex-wrap: wrap;}
        .aba-nav a { font-weight: bold; font-size: 1.05rem; padding: 5px 10px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;}
        .aba-nav a:hover { opacity: 1 !important; }
        .caixa-upload { border: 2px dashed var(--text-color); padding: 20px; border-radius: 10px; margin: 10px 0 25px 0; background-color: rgba(0,0,0,0.03); }
        .busca-admin-container { display: flex; gap: 10px; margin-bottom: 15px; }
        .busca-admin-container input { flex: 1; padding: 12px; border-radius: 8px; border: 1px solid var(--text-color); background: var(--bg-color); color: var(--text-color); outline: none; }
        .busca-admin-container button { padding: 12px 20px; border-radius: 8px; background: var(--text-color); color: var(--bg-color); font-weight: bold; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 5px; }
        .icon-title { display: inline-flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
    <header style="padding: 1rem;">
        <button class="theme-toggle" onclick="toggleTheme()" style="position: absolute; right: 20px;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg> Tema
        </button>
        <img id="logo-img" src="mimod_cacaubranco.png" alt="Mimo d'cacau Logo" class="logo" style="max-width: 150px;">
        <h2>Painel Admin</h2>
    </header>

    <div class="container" style="padding-top: 0;">
        <?php if ($error): ?><p style="color: red; text-align: center; font-weight: bold; font-size: 1.2rem;"><?= $error ?></p><?php endif; ?>
        <?php if ($success): ?><p style="color: var(--text-color); text-align: center; font-weight: bold; font-size: 1.2rem;"><?= $success ?></p><?php endif; ?>

        <?php if (!$is_logged): ?>
            <div class="admin-form" style="max-width: 400px; margin: 2rem auto;">
                <form method="POST">
                    <label>Usuário:</label><input type="text" name="user" required>
                    <label>Senha:</label><input type="password" name="pass" required>
                    <button type="submit" name="login" style="width: 100%; display: flex; justify-content: center; align-items: center; gap: 8px;">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                        Entrar no Sistema
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div style="text-align: right; margin-bottom: 20px; display: flex; justify-content: flex-end; gap: 15px;">
                <a href="index.php" target="_blank" style="font-weight: bold; display: inline-flex; align-items: center; gap: 5px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    Ver Site
                </a>
                <a href="admin.php?logout=1" style="font-weight: bold; display: inline-flex; align-items: center; gap: 5px; opacity: 0.7;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Sair
                </a>
            </div>

            <div class="aba-nav">
                <a href="admin.php?aba=pedidos" style="<?= $aba === 'pedidos' ? 'border-bottom: 3px solid var(--text-color);' : 'opacity: 0.6;' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg> Pedidos
                </a>
                <a href="admin.php?aba=cadastros" style="<?= $aba === 'cadastros' ? 'border-bottom: 3px solid var(--text-color);' : 'opacity: 0.6;' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg> Cadastrar
                </a>
                <a href="admin.php?aba=gerenciar" style="<?= $aba === 'gerenciar' ? 'border-bottom: 3px solid var(--text-color);' : 'opacity: 0.6;' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg> Gerenciar
                </a>
            </div>

            <?php if (isset($_GET['action']) && $_GET['action'] == 'edit_sabor' && isset($_GET['id'])):
                $stmt = $db->prepare("SELECT * FROM sabores WHERE id = ?"); $stmt->execute([$_GET['id']]); $sabor_edit = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
                <div class="admin-form">
                    <h3 class="icon-title"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Editar Sabor: <?= htmlspecialchars($sabor_edit['nome']) ?></h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="sabor_id" value="<?= $sabor_edit['id'] ?>">
                        <label>Nome do Sabor:</label><input type="text" name="nome_sabor" value="<?= htmlspecialchars($sabor_edit['nome']) ?>" required>
                        <label>Foto Atual:</label><br><img src="uploads/<?= htmlspecialchars($sabor_edit['imagem']) ?>" width="100" style="border-radius: 8px; margin-bottom: 10px;"><br>
                        <label>Trocar Foto:</label><div class="caixa-upload"><input type="file" name="imagem_sabor" accept="image/*"></div>
                        <div style="display: flex; gap: 10px;"><button type="submit" name="update_sabor" class="btn-action btn-success">Salvar Alterações</button><a href="admin.php?aba=gerenciar" class="btn-action">Cancelar</a></div>
                    </form>
                </div>

            <?php elseif (isset($_GET['action']) && $_GET['action'] == 'edit_produto' && isset($_GET['id'])):
                $stmt = $db->prepare("SELECT * FROM produtos WHERE id = ?"); $stmt->execute([$_GET['id']]); $produto_edit = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt_rels = $db->prepare("SELECT sabor_id FROM produto_sabor WHERE produto_id = ?"); $stmt_rels->execute([$_GET['id']]); $sabores_do_produto = $stmt_rels->fetchAll(PDO::FETCH_COLUMN);
            ?>
                <div class="admin-form">
                    <h3 class="icon-title"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Editar Produto: <?= htmlspecialchars($produto_edit['nome']) ?></h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="produto_id" value="<?= $produto_edit['id'] ?>">
                        <label>Nome do Produto:</label><input type="text" name="nome_produto" value="<?= htmlspecialchars($produto_edit['nome']) ?>" required>
                        <label>Preço (R$):</label><input type="number" step="0.01" name="preco_produto" value="<?= $produto_edit['preco'] ?>" required>
                        <label>Trocar Galeria de Fotos:</label><div class="caixa-upload"><input type="file" name="imagens_produto[]" accept="image/*" multiple></div>
                        <label>Descrição e Detalhes:</label><textarea name="descricao_produto" rows="4" required><?= htmlspecialchars($produto_edit['descricao']) ?></textarea>
                        <label>Sabores Vinculados:</label>
                        <div style="margin: 15px 0 25px 0;">
                            <?php foreach($sabores_existentes as $s): ?>
                                <label class="checkbox-wrapper"><input type="checkbox" name="sabores_selecionados[]" value="<?= $s['id'] ?>" <?= in_array($s['id'], $sabores_do_produto) ? 'checked' : '' ?>><span class="checkmark"></span> <?= htmlspecialchars($s['nome']) ?></label>
                            <?php endforeach; ?>
                        </div>
                        <div style="display: flex; gap: 10px;"><button type="submit" name="update_produto" class="btn-action btn-success">Salvar Alterações</button><a href="admin.php?aba=gerenciar" class="btn-action">Cancelar</a></div>
                    </form>
                </div>

            <?php elseif ($aba === 'pedidos'): ?>
                <div class="admin-form">
                    <h3 class="icon-title">Lista de Pedidos Recebidos</h3>
                    <div style="overflow-x: auto;">
                        <table class="list-table" style="min-width: 600px;">
                            <tr><th>ID</th><th>Cliente</th><th>Data</th><th>Total</th><th>Status</th><th>Ação</th></tr>
                            <?php foreach($pedidos as $ped): ?>
                            <tr>
                                <td>#<?= $ped['id'] ?></td>
                                <td><?= htmlspecialchars($ped['cliente_nome']) ?><br><small style="display:inline-flex; align-items:center; gap:4px; opacity:0.8; margin-top:4px;"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg> <?= htmlspecialchars($ped['cliente_whatsapp']) ?></small></td>
                                <td><?= date('d/m H:i', strtotime($ped['data'])) ?></td>
                                <td>R$ <?= number_format($ped['total'], 2, ',', '.') ?></td>
                                <td><span class="<?= $ped['status'] == 'Concluído' ? 'status-concluido' : 'status-pendente' ?>"><?= $ped['status'] ?></span></td>
                                <td>
                                    <?php if($ped['status'] != 'Concluído'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="pedido_id" value="<?= $ped['id'] ?>">
                                        <button type="submit" name="concluir_pedido" class="btn-action btn-success"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Concluir</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" style="background: var(--bg-color); font-size: 0.9em; padding: 15px;">
                                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg><strong>Endereço:</strong> <?= htmlspecialchars($ped['endereco']) ?></div>
                                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg><strong>Itens Solicitados:</strong></div>
                                    <div style="padding-left: 22px;">
                                        <?php 
                                            // AQUI MOSTRAMOS O SABOR SALVO NO PEDIDO!
                                            $stmt = $db->prepare("SELECT ip.quantidade, p.nome, ip.sabor FROM itens_pedido ip JOIN produtos p ON ip.produto_id = p.id WHERE ip.pedido_id = ?");
                                            $stmt->execute([$ped['id']]); $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                            foreach($itens as $item) { 
                                                $sabor_txt = !empty($item['sabor']) ? " <span style='opacity: 0.7;'>(Sabor: " . htmlspecialchars($item['sabor']) . ")</span>" : "";
                                                echo "<div style='display:flex; align-items:center; gap:6px;'><svg viewBox='0 0 24 24' width='12' height='12' fill='none' stroke='currentColor' stroke-width='2'><polyline points='15 10 20 15 15 20'></polyline><path d='M4 4v7a4 4 0 0 0 4 4h12'></path></svg> " . $item['quantidade'] . "x " . htmlspecialchars($item['nome']) . $sabor_txt . "</div>"; 
                                            }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($pedidos)): ?><tr><td colspan="6" style="text-align:center;">Nenhum pedido recebido ainda.</td></tr><?php endif; ?>
                        </table>
                    </div>
                </div>

            <?php elseif ($aba === 'cadastros'): ?>
                <div class="produtos-grid">
                    <div class="admin-form">
                        <h3 class="icon-title"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg> Cadastrar Novo Sabor</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <label>Nome do Sabor:</label><input type="text" name="nome_sabor" required>
                            <label>Foto do Sabor:</label><div class="caixa-upload"><input type="file" name="imagem_sabor" accept="image/*" required></div>
                            <button type="submit" name="add_sabor" style="width: 100%;">Salvar Sabor</button>
                        </form>
                    </div>

                    <div class="admin-form">
                        <h3 class="icon-title"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg> Cadastrar Novo Produto</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <label>Nome do Produto:</label><input type="text" name="nome_produto" required>
                            <label>Preço (R$):</label><input type="number" step="0.01" name="preco_produto" required>
                            <label>Fotos (Pode selecionar várias):</label><div class="caixa-upload"><input type="file" name="imagens_produto[]" accept="image/*" multiple required></div>
                            <label>Descrição e Detalhes:</label><textarea name="descricao_produto" rows="4" required></textarea>
                            <label>Sabores disponíveis neste produto:</label>
                            <div style="margin: 15px 0 25px 0;">
                                <?php foreach($sabores_existentes as $s): ?>
                                    <label class="checkbox-wrapper"><input type="checkbox" name="sabores_selecionados[]" value="<?= $s['id'] ?>"><span class="checkmark"></span> <?= htmlspecialchars($s['nome']) ?></label>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" name="add_produto" style="width: 100%;">Salvar Produto</button>
                        </form>
                    </div>
                </div>

            <?php elseif ($aba === 'gerenciar'): ?>
                <div class="produtos-grid">
                    <div class="admin-form" id="gerenciar-sabores">
                        <h3>Gerenciar Sabores</h3>
                        <form method="GET" class="busca-admin-container">
                            <input type="hidden" name="aba" value="gerenciar">
                            <input type="text" name="busca_sabor" placeholder="Buscar sabor..." value="<?= htmlspecialchars($_GET['busca_sabor'] ?? '') ?>">
                            <button type="submit"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> Buscar</button>
                            <?php if(isset($_GET['busca_sabor']) && $_GET['busca_sabor'] != ''): ?><a href="admin.php?aba=gerenciar" class="btn-action" style="padding: 12px 20px; text-decoration:none;">Limpar</a><?php endif; ?>
                        </form>
                        <div style="overflow-x: auto;">
                            <table class="list-table">
                                <?php foreach($sabores_existentes as $s): ?>
                                <tr>
                                    <td><img src="uploads/<?= htmlspecialchars($s['imagem']) ?>" width="40" height="40" style="border-radius: 5px; object-fit: cover;"></td>
                                    <td style="font-weight: bold; width: 100%;"><?= htmlspecialchars($s['nome']) ?></td>
                                    <td style="white-space: nowrap;">
                                        <a href="admin.php?aba=gerenciar&action=edit_sabor&id=<?= $s['id'] ?>" class="btn-action btn-edit"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Editar</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este sabor?')"><input type="hidden" name="sabor_id" value="<?= $s['id'] ?>"><button type="submit" name="delete_sabor" class="btn-action" style="color: #D32F2F; border-color: #D32F2F;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Excluir</button></form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($sabores_existentes)): ?><tr><td colspan="3" style="text-align: center;">Nenhum sabor encontrado.</td></tr><?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <div class="admin-form" id="gerenciar-produtos">
                        <h3>Gerenciar Produtos</h3>
                        <form method="GET" class="busca-admin-container">
                            <input type="hidden" name="aba" value="gerenciar">
                            <input type="text" name="busca_admin" placeholder="Buscar produto..." value="<?= htmlspecialchars($_GET['busca_admin'] ?? '') ?>">
                            <button type="submit"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> Buscar</button>
                            <?php if(isset($_GET['busca_admin']) && $_GET['busca_admin'] != ''): ?><a href="admin.php?aba=gerenciar" class="btn-action" style="padding: 12px 20px; text-decoration:none;">Limpar</a><?php endif; ?>
                        </form>
                        <div style="overflow-x: auto;">
                            <table class="list-table">
                                <?php foreach($produtos_existentes as $p): ?>
                                <tr>
                                    <td style="font-weight: bold; width: 100%;"><?= htmlspecialchars($p['nome']) ?></td>
                                    <td style="white-space: nowrap;">
                                        <a href="admin.php?aba=gerenciar&action=edit_produto&id=<?= $p['id'] ?>" class="btn-action btn-edit"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Editar</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir o produto e suas fotos?')"><input type="hidden" name="produto_id" value="<?= $p['id'] ?>"><button type="submit" name="delete_produto" class="btn-action" style="color: #D32F2F; border-color: #D32F2F;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg> Excluir</button></form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($produtos_existentes)): ?><tr><td colspan="2" style="text-align: center;">Nenhum produto encontrado.</td></tr><?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('busca_admin')) { const el = document.getElementById('gerenciar-produtos'); if (el) el.scrollIntoView({behavior: 'smooth', block: 'start'}); } 
            else if (urlParams.has('busca_sabor')) { const el = document.getElementById('gerenciar-sabores'); if (el) el.scrollIntoView({behavior: 'smooth', block: 'start'}); }
        };
    </script>
</body>
</html>
