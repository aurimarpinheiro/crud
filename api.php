<?php
header('Content-Type: application/json');

// Configuração de conexão com o banco de dados MySQL
$host = 'localhost';
$dbname = 'crud_php';
$username = 'root';  // Altere para o seu usuário
$password = 'levitico414';      // Altere para sua senha

// Função para conectar ao banco de dados
function connect() {
    global $host, $dbname, $username, $password;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die(json_encode(['error' => $e->getMessage()]));
    }
}

// Pega o método HTTP (GET, POST, PUT, DELETE)
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// CRUD
switch ($method) {
    case 'GET':
        // Retorna todos os registros
        $pdo = connect();
        $stmt = $pdo->query('SELECT * FROM items');
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($items);
        break;

    case 'POST':
        // Cria um novo registro
        if (isset($input['name'])) {
            $pdo = connect();
            $stmt = $pdo->prepare('INSERT INTO items (name) VALUES (:name)');
            $stmt->execute(['name' => $input['name']]);
            $id = $pdo->lastInsertId();
            echo json_encode(['id' => $id, 'name' => $input['name']]);
        }
        break;

    case 'PUT':
        // Atualiza um registro existente
        if (isset($input['id'], $input['name'])) {
            $pdo = connect();
            $stmt = $pdo->prepare('UPDATE items SET name = :name WHERE id = :id');
            $stmt->execute(['id' => $input['id'], 'name' => $input['name']]);
            echo json_encode(['status' => 'success']);
        }
        break;

    case 'DELETE':
        // Deleta um registro
        if (isset($input['id'])) {
            $pdo = connect();
            $stmt = $pdo->prepare('DELETE FROM items WHERE id = :id');
            $stmt->execute(['id' => $input['id']]);
            echo json_encode(['status' => 'success']);
        }
        break;

    default:
        echo json_encode(['status' => 'unsupported method']);
        break;
}
