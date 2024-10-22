<!-- form.php -->
<!DOCTYPE HTML>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form di Contatto</title>
    <style>
        .form-group {
            margin-bottom: 1rem;
        }
        .error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .success {
            color: #28a745;
        }
        .form-control {
            display: block;
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            margin-top: 0.5rem;
        }
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .loading {
            display: none;
            margin-left: 1rem;
        }
        button:disabled {
            opacity: 0.65;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    $v1 = rand(0, 20);
    $v2 = rand(0, 20);
    $sum = $v1 + $v2;
    $check_value = hash("sha256", $sum);
    ?>

    <div id="formContainer">
        <div id="alertBox" style="display: none;" role="alert"></div>
        
        <form id="contactForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="r" value="<?php echo $check_value; ?>">
            
            <div class="form-group">
                <label for="name">Nome *</label>
                <input type="text" id="name" name="name" class="form-control" required>
                <div class="error" id="nameError"></div>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" required>
                <div class="error" id="emailError"></div>
            </div>

            <div class="form-group">
                <label for="message">Messaggio *</label>
                <textarea id="message" name="message" class="form-control" rows="5" required></textarea>
                <div class="error" id="messageError"></div>
            </div>

            <div class="form-group">
                <label for="check">Per favore risolvi questa operazione: <?php echo $v1; ?> + <?php echo $v2; ?> = </label>
                <input type="number" id="check" name="check" class="form-control" required>
                <div class="error" id="checkError"></div>
            </div>

            <button type="submit" id="submitButton">Invia Messaggio</button>
            <span class="loading" id="loadingIndicator">Invio in corso...</span>
        </form>
    </div>

    <script>
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Reset precedenti errori
            clearErrors();
            
            // Mostra loading e disabilita il bottone
            const submitButton = document.getElementById('submitButton');
            const loadingIndicator = document.getElementById('loadingIndicator');
            submitButton.disabled = true;
            loadingIndicator.style.display = 'inline';
            
            try {
                const formData = new FormData(this);
                
                const response = await fetch('process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    this.reset();
                } else {
                    if (result.errors && result.errors.length > 0) {
                        result.errors.forEach(error => {
                            if (error.includes('email')) {
                                showFieldError('email', error);
                            } else if (error.includes('nome')) {
                                showFieldError('name', error);
                            } else if (error.includes('messaggio')) {
                                showFieldError('message', error);
                            } else if (error.includes('verifica')) {
                                showFieldError('check', error);
                            }
                        });
                    } else {
                        showAlert('danger', result.message || 'Si è verificato un errore durante l\'invio del messaggio.');
                    }
                }
            } catch (error) {
                showAlert('danger', 'Si è verificato un errore di connessione. Riprova più tardi.');
                console.error('Error:', error);
            } finally {
                submitButton.disabled = false;
                loadingIndicator.style.display = 'none';
            }
        });

        function showFieldError(fieldId, error) {
            const errorDiv = document.getElementById(fieldId + 'Error');
            const field = document.getElementById(fieldId);
            if (errorDiv && field) {
                errorDiv.textContent = error;
                field.classList.add('is-invalid');
            }
        }

        function clearErrors() {
            document.querySelectorAll('.error').forEach(el => el.textContent = '');
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.getElementById('alertBox').style.display = 'none';
        }

        function showAlert(type, message) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = `alert alert-${type}`;
            alertBox.textContent = message;
            alertBox.style.display = 'block';
            
            if (type === 'success') {
                alertBox.scrollIntoView({ behavior: 'smooth' });
            }
        }
    </script>
</body>
</html>
