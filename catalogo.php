<?php
// catalogo.php
require 'db.php';

$total_carrinho = 0;
if (isset($_SESSION['carrinho'])) { foreach ($_SESSION['carrinho'] as $qtd) { $total_carrinho += $qtd; } }

$busca = $_GET['busca'] ?? '';
if ($busca) {
    $stmt = $db->prepare("SELECT * FROM produtos WHERE nome LIKE :busca ORDER BY nome ASC");
    $stmt->execute(['busca' => "%$busca%"]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $produtos = $db->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio Completo - Mimo d'cacau</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header id="inicio">
        <div class="top-bar">
            <button class="theme-toggle" onclick="toggleTheme()">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                Tema
            </button>
            <a href="carrinho.php" class="cart-link">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                Carrinho (<?= $total_carrinho ?>)
            </a>
        </div>
        
        <img id="logo-img" src="mimod_cacaubranco.png" alt="Mimo d'cacau Logo" class="logo">
        
        <nav class="navbar">
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> Início</a></li>
                <li><a href="catalogo.php" class="nav-link" style="opacity:0.5;">Cardápio Completo</a></li>
                <li><a href="index.php#sobre" class="nav-link">Sobre Nós</a></li>
                <li><a href="index.php#contato" class="nav-link">Contato</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <section id="cardapio">
            <h2 class="section-title">Catálogo Completo</h2>
            
            <div class="busca-container">
                <form method="GET" action="catalogo.php">
                    <input type="text" name="busca" placeholder="Buscar por doce, mimo..." value="<?= htmlspecialchars($busca) ?>">
                    <button type="submit">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> 
                        Buscar
                    </button>
                    <?php if($busca): ?>
                        <a href="catalogo.php" style="margin-left: 15px; font-weight: bold; text-decoration: underline;">Limpar Filtro</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="produtos-grid">
                <?php if (count($produtos) > 0): ?>
                    <?php foreach ($produtos as $produto): ?>
                        <a href="produto.php?id=<?= $produto['id'] ?>" class="produto-card">
                            <?php if (!empty($produto['imagem'])): ?>
                                <img src="uploads/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/300x300?text=Sem+Foto" alt="Sem Foto" style="opacity: 0.5;">
                            <?php endif; ?>
                            
                            <h3><?= htmlspecialchars($produto['nome']) ?></h3>
                            <p class="preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                            <span class="btn-comprar">Ver Detalhes</span>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; width: 100%;">Nenhum produto encontrado.</p>
                <?php endif; ?>
            </div>
        </section>
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