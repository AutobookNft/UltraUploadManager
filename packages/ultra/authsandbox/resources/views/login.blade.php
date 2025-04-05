<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>console.log('Login')</script>

    <title>Login - Ultra Sandbox</title>
    <style>
        :root {
            --primary-color: #3f51b5;
            --primary-dark: #303f9f;
            --accent-color: #ff4081;
            --text-color: #333333;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --error-color: #f44336;
            --success-color: #4caf50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }

        .login-header {
            background-color: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.8;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 30px;
            background-color: var(--primary-color);
            transform: rotate(45deg);
        }

        .login-form {
            padding: 40px 30px 30px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            background-color: var(--light-gray);
            border: 1px solid var(--medium-gray);
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(63, 81, 181, 0.2);
            border-color: var(--primary-color);
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-button:hover {
            background-color: var(--primary-dark);
        }

        .error-message {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
            padding: 12px;
            border-radius: 4px;
            font-size: 14px;
            margin-top: 20px;
            border-left: 4px solid var(--error-color);
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: var(--text-color);
        }

        .footer a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Effetto pulsante */
        .login-button:active {
            transform: translateY(1px);
        }

        /* Animazione di entrata */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-container {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Ultra Sandbox</h1>
            <p>Accedi per continuare</p>
        </div>

        <div class="login-form">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Indirizzo Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="login-button">Accedi</button>

                @if($errors->any())
                <div class="error-message">
                    {{ $errors->first() }}
                </div>
                @endif
            </form>

            <div class="footer">
                <p>Non hai un account? <a href="#">Registrati</a></p>
                <p><a href="#">Password dimenticata?</a></p>
            </div>
        </div>
    </div>
</body>
</html>
