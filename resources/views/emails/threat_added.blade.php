<!DOCTYPE html>
<html>
<body>
    <h2>Olá, {{ $asset->manager->name ?? 'Gestor' }}!</h2>
    <p>Uma nova ameaça foi associada ao ativo <strong>{{ $asset->name }}</strong>.</p>
    <ul>
        <li><strong>Ameaça:</strong> {{ $threat->name }}</li>
        <li><strong>Descrição:</strong> {{ $threat->description }}</li>
    </ul>
    <p>Por favor, aceda ao sistema para avaliar o impacto e a probabilidade.</p>
</body>
</html>