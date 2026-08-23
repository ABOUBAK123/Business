<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bienvenue</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:12px;overflow:hidden;">
<tr>
<td style="background:#1e40af;padding:28px 32px;">
<h1 style="margin:0;color:#ffffff;font-size:20px;">{{ config('app.name') }}</h1>
</td>
</tr>
<tr>
<td style="padding:32px;">
<p style="margin:0 0 16px;color:#111827;font-size:16px;">Bonjour {{ $ownerName ?? 'et bienvenue' }},</p>
<p style="margin:0 0 16px;color:#374151;font-size:14px;line-height:1.6;">
    Votre boutique <strong>{{ $tenant->shop_name }}</strong> est maintenant prête sur {{ config('app.name') }}.
    Vous pouvez dès à présent gérer vos articles, vos ventes et votre équipe depuis votre tableau de bord.
</p>
<p style="margin:0 0 24px;color:#374151;font-size:14px;line-height:1.6;">
    Si vous avez la moindre question pour démarrer, notre équipe reste disponible.
</p>
<table role="presentation" cellpadding="0" cellspacing="0">
<tr>
<td style="border-radius:8px;background:#1e40af;">
<a href="{{ route('login') }}" style="display:inline-block;padding:12px 24px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;">
    Accéder à mon tableau de bord
</a>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td style="padding:20px 32px;background:#f9fafb;border-top:1px solid #f3f4f6;">
<p style="margin:0;color:#9ca3af;font-size:12px;">{{ config('app.name') }} — cet email a été envoyé automatiquement.</p>
</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
