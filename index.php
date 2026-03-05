<?php
// index.php
require 'db.php';

$total_carrinho = 0;
if (isset($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $qtd) { $total_carrinho += $qtd; }
}

$sabores = $db->query("SELECT * FROM sabores")->fetchAll(PDO::FETCH_ASSOC);
$produtos_recentes = $db->query("SELECT * FROM produtos ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mimo d'cacau - Inicial</title>
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
        <p>mimos em forma de doce</p>
        
        <nav class="navbar">
            <ul class="nav-list">
                <li><a href="catalogo.php" class="nav-link">Cardápio Completo</a></li>
                <li><a href="#sobre" class="nav-link">Sobre Nós</a></li>
                <li><a href="#entrega" class="nav-link">Pagamento & Entrega</a></li>
                <li><a href="#contato" class="nav-link">Contato</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        
        <?php if(isset($_GET['sucesso'])): ?>
            <div style="background: #25D366; color: white; padding: 20px; text-align: center; border-radius: 10px; margin-bottom: 30px; font-weight: bold; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                Pagamento iniciado! Seu pedido já está no nosso sistema. Entraremos em contato em breve.
            </div>
        <?php endif; ?>

        <section id="sabores">
            <h2 class="section-title">Nossos Sabores</h2>
            
            <div class="sabores-carousel-container">
                <div class="sabores-carousel-track" id="sabores-track">
                    <?php if (count($sabores) > 0): ?>
                        <?php foreach ($sabores as $sabor): ?>
                            <div class="sabores-carousel-slide">
                                <div class="sabor-card">
                                    <img src="uploads/<?= htmlspecialchars($sabor['imagem']) ?>" alt="<?= htmlspecialchars($sabor['nome']) ?>">
                                    <h3 style="font-size: 1.5rem;"><?= htmlspecialchars($sabor['nome']) ?></h3>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="sabores-carousel-slide">
                            <p style="text-align: center;">Sabores em breve...</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (count($sabores) > 1): ?>
                    <button class="sabores-btn prev" onclick="proximoSabor(-1, true)">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <button class="sabores-btn next" onclick="proximoSabor(1, true)">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                <?php endif; ?>
            </div>

            <h2 class="section-title" style="margin-top: 5rem;">Novidades</h2>
            <div class="produtos-grid">
                <?php if (count($produtos_recentes) > 0): ?>
                    <?php foreach ($produtos_recentes as $produto): ?>
                        <a href="produto.php?id=<?= $produto['id'] ?>" class="produto-card">
                            <span class="badge-novo">NOVO!</span>
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
                    <p style="text-align: center; width: 100%;">Nenhuma novidade encontrada.</p>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 3rem; margin-bottom: 5rem;">
                <a href="catalogo.php" class="btn-comprar" style="font-size: 1.1rem; padding: 15px 40px;">VER TODO O CARDÁPIO</a>
            </div>
        </section>

        <section id="sobre" class="info-section">
            <h2 class="section-title">Nossa História</h2>
            <p>Acreditamos que todo dia pede um pequeno prazer em meio a rotina, um doce; uma pausa; um mimo.</p>
            <p>Sendo assim, criamos a <strong>Mimo d'cacau</strong>. Para aqueles momentos do dia que pedem uma pausa.</p>
            <p style="font-size: 1.4rem; margin-top: 2rem;"><strong>Presenteie quem você ama, uma amizade ou a si mesmo.<br>Tenha seu momento Mimo.</strong></p>
        </section>

        <section id="entrega" class="info-section">
            <h2 class="section-title">Como funciona o pagamento e a entrega?</h2>
            <p>Nosso processo de encomenda foi pensado para ser rápido, seguro e muito humano.</p>
            <p>Adicione os mimos ao carrinho e finalize seu pedido com total segurança, <strong>pagando diretamente aqui no site através da nossa integração com o Mercado Pago</strong>.</p>
            <p>Assim que o seu pagamento for processado, recebemos o alerta na nossa cozinha. <strong>Em seguida, nossa equipe chamará você no WhatsApp</strong> para combinar os detalhes de entrega ou o horário de retirada do seu mimo.</p>
        </section>

        <section id="contato" class="info-section" style="margin-bottom: 0;">
            <h2 class="section-title">Fale Conosco</h2>
            <p>Ficou com alguma dúvida sobre os ingredientes, precisa de uma encomenda grande para festas ou quer montar um mimo personalizado?</p>
            <p>Chame a gente no WhatsApp, adoraremos atender você!</p>
            
            <a href="https://wa.me/5500000000000" class="btn-whatsapp" target="_blank">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                Falar no WhatsApp
            </a>
        </section>
    </div>

    <footer style="text-align: center; padding: 2rem; margin-top: 2rem; border-top: 1px solid var(--text-color); font-size: 0.9rem; opacity: 0.8;">
        <p>&copy; <?= date('Y') ?> Mimo d'cacau. Todos os direitos reservados.</p>
        <p><a href="#inicio" style="display:inline-flex; align-items:center; gap:5px;"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg> Voltar ao topo</a></p>
    </footer>

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
        
        // EFEITO 3D
        const infoSections = document.querySelectorAll('.info-section');
        infoSections.forEach(section => {
            section.addEventListener('mousemove', (e) => {
                const rect = section.getBoundingClientRect();
                const x = e.clientX - rect.left; const y = e.clientY - rect.top;
                const rotateX = ((y - rect.height/2) / (rect.height/2)) * -8; const rotateY = ((x - rect.width/2) / (rect.width/2)) * 8;
                section.style.transition = 'none';
                section.style.setProperty('--rx', `${rotateX}deg`); section.style.setProperty('--ry', `${rotateY}deg`);
                section.style.setProperty('--px', `${(x / rect.width) * 100}%`); section.style.setProperty('--py', `${(y / rect.height) * 100}%`);
            });
            section.addEventListener('mouseleave', () => {
                section.style.transition = 'transform 0.5s ease-out';
                section.style.setProperty('--rx', '0deg'); section.style.setProperty('--ry', '0deg');
                section.style.setProperty('--px', '50%'); section.style.setProperty('--py', '50%');
            });
        });

        // ==========================================
        // LÓGICA DO CARROSSEL DE SABORES (AUTO + PAUSA)
        // ==========================================
        const saboresTrack = document.getElementById('sabores-track');
        const saboresSlides = document.querySelectorAll('.sabores-carousel-slide');
        const totalSabores = saboresSlides.length;
        let saborAtual = 0;
        let saborInterval;
        let saborTimeout;

        function atualizarSabores() {
            if (totalSabores <= 1) return;
            
            // Lógica para mostrar 1 slide no celular ou 2 no computador
            const itemsPerView = window.innerWidth >= 768 ? 2 : 1;
            const maxIndex = totalSabores - itemsPerView;
            
            // Garantir que não arraste para o vazio
            if (saborAtual > maxIndex) saborAtual = 0;
            if (saborAtual < 0) saborAtual = maxIndex;
            
            const percentage = -(saborAtual * (100 / itemsPerView));
            saboresTrack.style.transform = `translateX(${percentage}%)`;
        }

        function proximoSabor(direcao, acaoManual = false) {
            if (totalSabores <= 1) return;
            saborAtual += direcao;
            
            const itemsPerView = window.innerWidth >= 768 ? 2 : 1;
            const maxIndex = totalSabores - itemsPerView;
            
            if (saborAtual > maxIndex) saborAtual = 0;
            if (saborAtual < 0) saborAtual = maxIndex;
            
            atualizarSabores();

            // Se o usuário clicou no botão, pausamos o loop automático
            if (acaoManual) {
                clearInterval(saborInterval); // Para a passagem de 4s
                clearTimeout(saborTimeout);   // Limpa pausas anteriores
                
                // Configura para voltar a rodar sozinho depois de 15 segundos
                saborTimeout = setTimeout(iniciarCarrosselSabores, 15000);
            }
        }

        function iniciarCarrosselSabores() {
            if (totalSabores <= 1) return;
            clearInterval(saborInterval);
            
            // Passa para o lado de 4 em 4 segundos
            saborInterval = setInterval(() => {
                proximoSabor(1, false);
            }, 4000);
        }

        // Dá o play assim que a página carrega
        iniciarCarrosselSabores();
        
        // Se a pessoa virar o celular ou mudar tamanho da janela, ajeita o carrossel
        window.addEventListener('resize', () => {
            const itemsPerView = window.innerWidth >= 768 ? 2 : 1;
            const maxIndex = totalSabores - itemsPerView;
            if (saborAtual > maxIndex) saborAtual = maxIndex;
            atualizarSabores();
        });
    </script>
</body>
</html>