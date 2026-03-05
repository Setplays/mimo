<?php
// db.php
session_start();

try {
    $db = new PDO('sqlite:mimo.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec("CREATE TABLE IF NOT EXISTS sabores (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, imagem TEXT)");
    $db->exec("CREATE TABLE IF NOT EXISTS produtos (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, descricao TEXT)");
    $db->exec("CREATE TABLE IF NOT EXISTS produto_sabor (produto_id INTEGER, sabor_id INTEGER)");

    try { $db->exec("ALTER TABLE produtos ADD COLUMN imagem TEXT"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE produtos ADD COLUMN preco REAL DEFAULT 0.00"); } catch (Exception $e) {}

    $db->exec("CREATE TABLE IF NOT EXISTS produto_imagens (id INTEGER PRIMARY KEY AUTOINCREMENT, produto_id INTEGER, imagem TEXT)");

    $db->exec("CREATE TABLE IF NOT EXISTS pedidos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cliente_nome TEXT,
        cliente_whatsapp TEXT,
        endereco TEXT,
        total REAL,
        status TEXT DEFAULT 'Em Preparação',
        data DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS itens_pedido (
        pedido_id INTEGER,
        produto_id INTEGER,
        quantidade INTEGER,
        preco REAL
    )");

    // NOVA ATUALIZAÇÃO: Adiciona a coluna sabor para gravar a escolha do cliente no pedido
    try { $db->exec("ALTER TABLE itens_pedido ADD COLUMN sabor TEXT"); } catch (Exception $e) {}

} catch (Exception $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}
?>