<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — SIGEM TecNM Veracruz</title>
    <link rel="icon" href="{{ asset('images/tecnm-logo.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,500;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f0f4f8;
            --sidebar-bg: #0b1d3a;
            --card-bg: #ffffff;
            --accent: #1b65d4;
            --accent-light: rgba(27, 101, 212, 0.15);
            --accent-dark: #0e4bad;
            --success: #0f9d58;
            --warning: #f4a623;
            --danger: #d93025;
            --danger-light: #fce8e6;
            --text-primary: #1a1a2e;
            --text-secondary: #5f6b7a;
            --text-muted: #8b95a5;
            --border: #e2e8f0;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow-x: hidden;
            background: var(--bg);
        }

        .bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-image: url('{{ asset("images/fondo.jpg") }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            z-index: -2;
        }
        
        .bg-overlay::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 29, 58, 0.75);
            z-index: -1;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            z-index: 1;
            animation: slideUp 0.6s ease forwards;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 40px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .brand-header {
            margin-bottom: 24px;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent), #4f9cf7);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
            color: #fff;
            margin: 0 auto 12px;
            box-shadow: 0 4px 10px rgba(27,101,212,0.4);
        }

        .brand-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--sidebar-bg);
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .logos-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .logos-container img {
            height: 45px;
            object-fit: contain;
        }

        .login-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .login-sub {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 24px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            font-family: inherit;
            font-size: 15px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            transition: all 0.2s;
            outline: none;
            background: #fff;
            color: var(--text-primary);
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .error-message {
            background: var(--danger-light);
            color: var(--danger);
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            text-align: left;
            border: 1px solid rgba(217, 48, 37, 0.2);
        }

        .btn-submit {
            width: 100%;
            background: var(--accent);
            color: #fff;
            padding: 14px;
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(27,101,212,0.3);
            margin-bottom: 20px;
        }

        .btn-submit:hover {
            background: var(--accent-dark);
            box-shadow: 0 6px 16px rgba(27,101,212,0.4);
            transform: translateY(-2px);
        }

        .tabs-container {
            display: flex;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }

        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-bottom: 2px solid transparent;
        }

        .tab:hover {
            color: var(--text-primary);
            background: rgba(0,0,0,0.02);
        }

        .tab.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .back-link-container {
            margin-top: 24px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--accent);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

    <div class="bg-overlay"></div>

    <div class="login-container">
        <div class="login-card">
            
            <div class="brand-header">
                <div class="brand-icon">S</div>
                <div class="brand-title">SIGEM</div>
            </div>

            <div class="logos-container">
                <img src="{{ asset('images/tecnm-logo.png') }}" alt="TecNM">
                <img src="{{ asset('images/itv-logo.png') }}" alt="ITV">
            </div>

            <div class="tabs-container">
                <div class="tab active" id="tab-alumno" onclick="switchTab('alumno')">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Alumnos
                </div>
                <div class="tab" id="tab-personal" onclick="switchTab('personal')">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Personal
                </div>
            </div>

            <h1 class="login-title">Iniciar sesión</h1>
            <p class="login-sub" id="login-subtitle">Acceso para alumnos de servicio social</p>

            @if($errors->any())
                <div class="error-message">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <input type="hidden" name="tipo_acceso" id="tipo_acceso" value="alumno">
                
                <div class="form-group">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="tu@tecnm.edu.mx" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="remember" name="remember" style="width: 16px; height: 16px; accent-color: var(--accent);">
                    <label for="remember" style="font-size: 14px; color: var(--text-secondary); cursor: pointer;">Recordarme</label>
                </div>

                <button type="submit" class="btn-submit">Iniciar sesión</button>
            </form>

            <div class="back-link-container">
                <a href="{{ url('/') }}" class="back-link">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Volver a la página principal
                </a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.getElementById('tab-alumno').classList.remove('active');
            document.getElementById('tab-personal').classList.remove('active');
            
            document.getElementById('tab-' + tab).classList.add('active');
            document.getElementById('tipo_acceso').value = tab;
            
            if (tab === 'alumno') {
                document.getElementById('login-subtitle').innerText = 'Acceso para alumnos de servicio social';
            } else {
                document.getElementById('login-subtitle').innerText = 'Acceso para personal administrativo';
            }
        }
    </script>
</body>
</html>
