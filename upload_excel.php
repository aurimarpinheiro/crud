<?php
require 'vendor/autoload.php';  // Para incluir o PHPSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica se o arquivo foi enviado
    if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['file']['tmp_name'];

        try {
            // Carrega o arquivo Excel
            $spreadsheet = IOFactory::load($fileTmpPath);
            $pdo = connect();
            $pdo->beginTransaction();  // Inicia uma transação para maior performance

            $stmt = $pdo->prepare('INSERT INTO items (name) VALUES (:name)');

            $totalInsertedRows = 0;
            $validSheets = ['Plan1', 'Plan2'];  // Abas que queremos processar
            $totalSheets = 2;  // Apenas duas abas são consideradas
            $progressPerSheet = 100 / $totalSheets;  // Progresso para cada aba

            // Itera sobre todas as abas (guias) da planilha
            foreach ($spreadsheet->getAllSheets() as $sheetIndex => $sheet) {
                $sheetName = $sheet->getTitle();  // Obtém o nome da aba
                echo json_encode(['planilha' => $sheetName]);
                
                // Verifica se o nome da aba está entre as desejadas
                if (in_array($sheetName, $validSheets)) {
                    $rows = $sheet->toArray(null, true, true, true);  // Converte as linhas da aba em um array
                    $totalRows = count($rows);  // Número de linhas na aba
                    $insertedRowsInSheet = 0;  // Contador para o progresso por aba

                    // Processa cada linha da aba
                    foreach ($rows as $row) {
                        $name = trim($row['A']);  // Coluna A deve conter o nome do item
                        if (!empty($name)) {
                            $stmt->execute(['name' => $name]);
                            $totalInsertedRows++;
                            $insertedRowsInSheet++;

                            // Simulando o progresso para a barra no frontend
                            $progress = round(($insertedRowsInSheet / $totalRows) * $progressPerSheet + ($sheetIndex * $progressPerSheet));
                            echo json_encode(['progress' => $progress]);

                            // Envia um pequeno delay para a barra de progresso ser atualizada corretamente no frontend (opcional)
                            usleep(100000);  // 100 milissegundos de pausa
                        }
                    }
                }
            }

            $pdo->commit();  // Confirma a transação
            echo json_encode(['success' => true, 'message' => "$totalInsertedRows itens inseridos com sucesso."]);
        } catch (Exception $e) {
            $pdo->rollBack();  // Desfaz a transação em caso de erro
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
