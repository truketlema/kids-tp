<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Family invitation</title>
</head>
<body>
    <h1>Join {{ $familyName }} on Kids TP</h1>
    <p>{{ $inviterName }} invited you to join their family on Kids TP.</p>
    <p><a href="{{ $inviteUrl }}">Accept family invitation</a></p>
    <p>This invitation expires on {{ $expiresAt->format('F j, Y \a\t g:i A') }}.</p>
    <p>If you were not expecting this invitation, you can ignore this email.</p>
</body>
</html>
