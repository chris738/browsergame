<?php
require_once 'backend.php';

// CLI-Testprogramm zum Testen des bereitgestellten Programms
class CliTest {

    public function run($args) {
        if (count($args) < 2) {
            echo "Usage: php cli_test.php <settlementId>\n";
            exit(1);
        }

        $settlementId = (int)$args[1];

        try {
            $data = $this->getFrontendData($settlementId);
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    private function getFrontendData() {
        $resources = $this->database->getResources(5);

        if (!$resources) {
            return ['error' => 'Ressourcen konnten nicht abgerufen werden.'];
        }
        return [
            'resources' => [
                'wood' => $resources['wood'],
                'stone' => $resources['stone'],
                'ore' => $resources['ore']
            ]
        ];
    }
}

// Instanziiere die Database-Klasse und führe den Test aus
$test = new CliTest();
$test->run($argv);

?>
