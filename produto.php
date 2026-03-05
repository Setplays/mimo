<?php
// produto.php
require 'db.php';

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }

$stmt = $db->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->execute([$_GET['id']]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) { die("Produto não encontrado."); }

$stmt_imagens = $db->prepare("SELECT imagem FROM produto_imagens WHERE produto_id = ?");
$stmt_imagens->execute([$produto['id']]);
$galeria = $stmt_imagens->fetchAll(PDO::FETCH_ASSOC);

if (empty($galeria) && !empty($produto['imagem'])) { $galeria[] = ['imagem' => $produto['imagem']]; }

$stmt_sabores = $db->prepare("SELECT s.nome FROM sabores s JOIN produto_sabor ps ON s.id = ps.sabor_id WHERE ps.produto_id = ?");
$stmt_sabores->execute([$produto['id']]);
$sabores = $stmt_sabores->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($produto['nome']) ?> - Mimo d'cacau</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="top-bar">
            <button class="theme-toggle" onclick="toggleTheme()">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg> Tema
            </button>
        </div>
        
        <img id="logo-img" src="mimod_cacaubranco.png" alt="Mimo d'cacau Logo" class="logo">
        <p><a href="catalogo.php"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg> Voltar ao Catálogo</a></p>
    </header>

    <div class="container">
        <div class="produto-detalhe">
            
            <div class="carousel-container">
                <div class="carousel-track" id="carousel-track">
                    <?php if (count($galeria) > 0): ?>
                        <?php foreach($galeria as $img): ?>
                            <img src="uploads/<?= htmlspecialchars($img['imagem']) ?>" class="carousel-slide" alt="<?= htmlspecialchars($produto['nome']) ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <img src="https://via.placeholder.com/450x450?text=Sem+Foto" class="carousel-slide" alt="Sem Foto">
                    <?php endif; ?>
                </div>
                
                <?php if (count($galeria) > 1): ?>
                    <button class="carousel-btn prev" onclick="mudarSlide(-1)"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg></button>
                    <button class="carousel-btn next" onclick="mudarSlide(1)"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
                <?php endif; ?>
            </div>
            
            <div class="produto-info">
                <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?= htmlspecialchars($produto['nome']) ?></h1>
                <p class="preco" style="font-size: 2rem;">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                
                <h3 style="margin-top: 20px;">Descrição:</h3>
                <p><?= nl2br(htmlspecialchars($produto['descricao'])) ?></p>

                <form action="carrinho.php" method="POST" class="admin-form" style="margin-top: 30px; padding: 20px;">
                    <input type="hidden" name="add_to_cart" value="1">
                    <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                    
                    <label style="font-weight: bold; font-size: 1.1rem; display: block; margin-bottom: 5px;">Quantidade Total:</label>
                    <input type="number" id="input-qtd" name="quantidade" value="1" min="1" required style="width: 100px; font-size: 1.2rem; margin-bottom: 20px; text-align: center;">
                    
                    <?php if (count($sabores) > 0): ?>
                        <div id="area-sabores" style="background: rgba(0,0,0,0.05); padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                            <p style="font-weight: bold; margin-bottom: 15px; font-size: 1.05rem;">
                                Distribua os sabores <br>
                                <span style="font-weight: normal; font-size: 0.9rem;">Faltam selecionar: <strong id="qtd-restante" style="font-size: 1.2rem;">1</strong> unid.</span>
                            </p>
                            
                            <?php foreach($sabores as $s): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 8px;">
                                    <span style="font-weight: bold; opacity: 0.9;"><?= htmlspecialchars($s['nome']) ?></span>
                                    
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <input type="hidden" name="sabor_nome[]" value="<?= htmlspecialchars($s['nome']) ?>">
                                        
                                        <button type="button" class="btn-sabor-minus" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--text-color); background: transparent; color: var(--text-color); cursor: pointer; font-weight: bold; font-size: 1.2rem; display: flex; align-items: center; justify-content: center;">-</button>
                                        
                                        <input type="number" name="sabor_qtd[]" class="sabor-qtd-input" value="0" min="0" readonly style="width: 40px; text-align: center; border: none; background: transparent; font-weight: bold; font-size: 1.1rem; color: var(--text-color); margin: 0; padding: 0;">
                                        
                                        <button type="button" class="btn-sabor-plus" style="width: 32px; height: 32px; border-radius: 8px; border: none; background: var(--text-color); color: var(--bg-color); cursor: pointer; font-weight: bold; font-size: 1.2rem; display: flex; align-items: center; justify-content: center;">+</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" id="btn-add-cart" style="width: 100%; font-size: 1.1rem; margin-top: 10px;">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                        Adicionar ao Carrinho
                    </button>
                </form>
            </div>
        </div>
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

        let slideAtual = 0;
        const track = document.getElementById('carousel-track');
        const slides = document.querySelectorAll('.carousel-slide');
        const totalSlides = slides.length;
        function atualizarCarrossel() { if (totalSlides <= 1) return; track.style.transform = `translateX(${-(slideAtual * 100)}%)`; }
        function mudarSlide(direcao) { if (totalSlides <= 1) return; slideAtual = (slideAtual + direcao + totalSlides) % totalSlides; atualizarCarrossel(); }
        if (totalSlides > 1) { setInterval(() => { mudarSlide(1); }, 3000); }

        // ==========================================
        // LÓGICA DE DISTRIBUIÇÃO DE SABORES
        // ==========================================
        <?php if (count($sabores) > 0): ?>
            const inputTotal = document.getElementById('input-qtd');
            const spanRestante = document.getElementById('qtd-restante');
            const btnSubmit = document.getElementById('btn-add-cart');

            const saborRows = document.querySelectorAll('.sabor-qtd-input');
            const btnsMinus = document.querySelectorAll('.btn-sabor-minus');
            const btnsPlus = document.querySelectorAll('.btn-sabor-plus');

            function atualizarRestante() {
                let totalDesejado = parseInt(inputTotal.value) || 1;
                let somaSabores = 0;
                
                saborRows.forEach(input => { somaSabores += parseInt(input.value) || 0; });
                let restante = totalDesejado - somaSabores;

                // Se o usuário diminuir a QTD total para um valor menor do que ele já tinha distribuído, reseta tudo
                if (restante < 0) {
                    saborRows.forEach(input => input.value = 0);
                    somaSabores = 0;
                    restante = totalDesejado;
                }

                spanRestante.innerText = restante;

                // Libera ou bloqueia o botão de comprar
                if (restante === 0) {
                    spanRestante.style.color = '#25D366'; // Verde de sucesso
                    btnSubmit.disabled = false;
                    btnSubmit.style.opacity = '1';
                    btnSubmit.style.cursor = 'pointer';
                    btnSubmit.innerHTML = `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg> Adicionar ao Carrinho`;
                } else {
                    spanRestante.style.color = '#D32F2F'; // Vermelho de aviso
                    btnSubmit.disabled = true;
                    btnSubmit.style.opacity = '0.5';
                    btnSubmit.style.cursor = 'not-allowed';
                    btnSubmit.innerHTML = `Distribua os sabores para comprar`;
                }

                // Ativa/Desativa botões + e -
                btnsPlus.forEach(btn => {
                    btn.disabled = restante === 0;
                    btn.style.opacity = restante === 0 ? '0.3' : '1';
                    btn.style.cursor = restante === 0 ? 'not-allowed' : 'pointer';
                });

                btnsMinus.forEach((btn, index) => {
                    let valAtual = parseInt(saborRows[index].value) || 0;
                    btn.disabled = valAtual === 0;
                    btn.style.opacity = valAtual === 0 ? '0.3' : '1';
                    btn.style.cursor = valAtual === 0 ? 'not-allowed' : 'pointer';
                });
            }

            // Evento para quando mudar a quantidade geral
            inputTotal.addEventListener('input', atualizarRestante);

            // Cliques nos botões de +
            btnsPlus.forEach((btn, index) => {
                btn.addEventListener('click', () => {
                    let valAtual = parseInt(saborRows[index].value) || 0;
                    let totalDesejado = parseInt(inputTotal.value) || 1;
                    let somaSabores = 0;
                    saborRows.forEach(input => { somaSabores += parseInt(input.value) || 0; });

                    if (somaSabores < totalDesejado) {
                        saborRows[index].value = valAtual + 1;
                        atualizarRestante();
                    }
                });
            });

            // Cliques nos botões de -
            btnsMinus.forEach((btn, index) => {
                btn.addEventListener('click', () => {
                    let valAtual = parseInt(saborRows[index].value) || 0;
                    if (valAtual > 0) {
                        saborRows[index].value = valAtual - 1;
                        atualizarRestante();
                    }
                });
            });

            // Roda uma vez no início para bloquear o botão até o cliente escolher o sabor 1
            atualizarRestante();
        <?php endif; ?>
    </script>
</body>
</html>