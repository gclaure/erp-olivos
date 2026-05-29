<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <title>Restablecer Contraseña - {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            -webkit-font-smoothing: antialiased;
            padding: 40px 16px;
        }

        .wrapper {
            max-width: 580px;
            margin: 0 auto;
        }

        /* ── TOP BRAND ── */
        .top-brand {
            text-align: center;
            margin-bottom: 24px;
        }
        .top-brand-inner {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .top-brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #137fec;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .top-brand-icon img {
            width: 22px;
            height: 22px;
            filter: brightness(0) invert(1);
        }
        .top-brand-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .top-brand-name span {
            font-weight: 300;
            color: #64748b;
        }

        /* ── CARD ── */
        .card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08), 0 1px 4px rgba(0,0,0,0.04);
        }

        /* Header */
        .card-header {
            background: linear-gradient(-45deg, #0a1628, #0f1f3d, #0c1a32, #111827);
            padding: 40px 40px 48px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .card-header::before {
            content: '';
            position: absolute;
            top: -40%; left: -20%;
            width: 60%; height: 160%;
            background: rgba(19,127,236,0.18);
            border-radius: 9999px;
            filter: blur(60px);
            text-align: center;
        }
        .card-header::after {
            content: '';
            position: absolute;
            bottom: -40%; right: -10%;
            width: 50%; height: 150%;
            background: rgba(14,165,233,0.12);
            border-radius: 9999px;
            filter: blur(60px);
            text-align: center;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(19,127,236,0.2);
            border: 1px solid rgba(19,127,236,0.3);
            border-radius: 9999px;
            padding: 6px 14px;
            margin-bottom: 20px;
        }
        .header-badge-dot {
            width: 7px; height: 7px;
            border-radius: 9999px;
            background: #34d399;
            display: inline-block;
        }
        .header-badge-text {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #94a3b8;
        }

        .header-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: #137fec;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(19,127,236,0.4);
        }
        .header-icon-wrap svg {
            width: 32px; height: 32px;
            fill: white;
        }

        .header-title {
            font-size: 1.75rem;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: -0.03em;
            line-height: 1.1;
            margin-bottom: 10px;
        }
        .header-subtitle {
            font-size: 0.9rem;
            color: #94a3b8;
            font-weight: 400;
            line-height: 1.6;
        }

        /* Body */
        .card-body {
            padding: 40px;
        }

        .greeting {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .message {
            font-size: 0.9rem;
            color: #475569;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        /* CTA Button */
        .cta-wrap {
            text-align: center;
            margin-bottom: 32px;
        }
        .cta-btn {
            display: inline-block;
            background: #137fec;
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 14px;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            box-shadow: 0 8px 32px rgba(19,127,236,0.35);
        }

        /* Info box */
        .info-box {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 28px;
        }
        .info-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .info-text {
            font-size: 0.8rem;
            color: #64748b;
            line-height: 1.6;
        }
        .info-text strong { color: #0f172a; }

        /* Divider */
        .divider {
            height: 1px;
            background: #e2e8f0;
            margin: 28px 0;
        }

        /* Fallback URL */
        .fallback {
            font-size: 0.78rem;
            color: #94a3b8;
            line-height: 1.7;
        }
        .fallback a {
            color: #137fec;
            word-break: break-all;
        }

        /* Signature */
        .signature {
            font-size: 0.875rem;
            color: #475569;
            line-height: 1.7;
            margin-top: 24px;
        }
        .signature strong { color: #0f172a; }

        /* Footer */
        .card-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 40px;
            text-align: center;
        }
        .footer-links {
            margin-bottom: 12px;
        }
        .footer-links a {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            margin: 0 10px;
        }
        .footer-links a:hover { color: #137fec; }
        .footer-copy {
            font-size: 0.7rem;
            color: #94a3b8;
            letter-spacing: 0.03em;
        }

        /* Bottom note */
        .bottom-note {
            text-align: center;
            margin-top: 20px;
            font-size: 0.75rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Card --}}
    <div class="card">

        {{-- Header --}}
        <div class="card-header">
            <div class="header-content">
                <h1 class="header-title">Restablecer Contraseña</h1>
                <p class="header-subtitle">Solicitud de restablecimiento de acceso seguro</p>
            </div>
        </div>

        {{-- Body --}}
        <div class="card-body">

            <p class="greeting">Hola, {{ $userName }} 👋</p>

            <p class="message">
                Has recibido este correo porque solicitaste un restablecimiento de contraseña para tu cuenta en nuestro sistema de inventario. Haz clic en el botón a continuación para crear una nueva contraseña.
            </p>

            {{-- CTA --}}
            <div class="cta-wrap">
                <a href="{{ $url }}" class="cta-btn" target="_blank">
                    Restablecer Contraseña →
                </a>
            </div>

            {{-- Info box --}}
            <div class="info-box text-center">
                <p class="info-text">
                    Este enlace caducará en <strong>{{ $expire }} minutos</strong>. Si no realizaste esta solicitud, puedes ignorar este mensaje con total seguridad — tu contraseña no será modificada.
                </p>
            </div>

            {{-- Signature --}}
            <p class="signature">
                Saludos,<br/>
                <strong>El equipo de {{ config('app.name') }}</strong>
            </p>

            <div class="divider"></div>

            {{-- Fallback URL --}}
            <p class="fallback">
                Si tienes problemas al hacer clic en el botón, copia y pega la siguiente URL en tu navegador:<br/>
                <a href="{{ $url }}">{{ $url }}</a>
            </p>

        </div>

        {{-- Footer --}}
        <div class="card-footer">
            <div class="footer-links">
                <a href="#">Soporte Técnico</a>
                <a href="#">Política de Privacidad</a>
                <a href="#">Términos de Uso</a>
            </div>
            <p class="footer-copy">
                &copy; {{ date('Y') }} {{ config('app.name') }} Enterprise. Todos los derechos reservados.
            </p>
        </div>

    </div>

    {{-- Bottom note --}}
    <p class="bottom-note">
        Este es un correo automático, por favor no responda a este mensaje.
    </p>

</div>
</body>
</html>