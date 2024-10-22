<?php
session_start();

// Funzione per sanitizzare gli input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Funzione per validare il form
function validateForm($data) {
    $errors = [];
    
    // Validazione email
    if (empty($data['email'])) {
        $errors[] = "L'email è obbligatoria";
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'indirizzo email non è valido";
    }
    
    // Validazione nome
    if (empty($data['name'])) {
        $errors[] = "Il nome è obbligatorio";
    } elseif (strlen($data['name']) < 2) {
        $errors[] = "Il nome deve contenere almeno 2 caratteri";
    }
    
    // Validazione messaggio
    if (empty($data['message'])) {
        $errors[] = "Il messaggio è obbligatorio";
    } elseif (strlen($data['message']) < 10) {
        $errors[] = "Il messaggio deve contenere almeno 10 caratteri";
    }
    
    // Validazione check anti-spam
    if (!isset($data['check']) || !isset($data['r'])) {
        $errors[] = "La verifica anti-spam è obbligatoria";
    } elseif (!filter_var($data['check'], FILTER_VALIDATE_INT) || 
              (hash("sha256", intval($data['check'])) !== $data['r'])) {
        $errors[] = "La verifica anti-spam non è corretta";
    }
    
    return $errors;
}

// Inizializza la risposta
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Verifica se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = "Errore di validazione del form";
        echo json_encode($response);
        exit;
    }
    
    // Raccogli i dati del form
    $formData = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'message' => $_POST['message'] ?? '',
        'check' => $_POST['check'] ?? '',
        'r' => $_POST['r'] ?? ''
    ];
    
    // Valida il form
    $errors = validateForm($formData);
    
    if (empty($errors)) {
        // Sanitizza i dati
        $name = sanitizeInput($formData['name']);
        $email = filter_var($formData['email'], FILTER_SANITIZE_EMAIL);
        $message = sanitizeInput($formData['message']);
        
        // Prepara l'email
        $to = "info@habitatpsicologia.it";
        $subject = "Messaggio da {$name} dal sito www.habitatpsicologia.it";
        $messageBody = "{$name} ti ha scritto:\r\n\r\n{$message}";
        
        // Headers dell'email
        $headers = [
            'From' => $email,
            'Reply-To' => $email,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8'
        ];
        
        // Invia l'email
        try {
            if (mail($to, $subject, $messageBody, implode("\r\n", array_map(
                function ($v, $k) { return "$k: $v"; },
                $headers,
                array_keys($headers)
            )))) {
                $response['success'] = true;
                $response['message'] = "Grazie! Il tuo messaggio è stato inviato con successo.";
            } else {
                $response['message'] = "Si è verificato un errore durante l'invio del messaggio. Per favore riprova più tardi.";
            }
        } catch (Exception $e) {
            $response['message'] = "Si è verificato un errore durante l'invio del messaggio";
            error_log("Errore invio email: " . $e->getMessage());
        }
    } else {
        $response['errors'] = $errors;
        $response['message'] = "Si prega di correggere gli errori nel form";
    }
}

// Genera nuovo CSRF token per il prossimo invio
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Imposta gli headers per la risposta JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode($response);
