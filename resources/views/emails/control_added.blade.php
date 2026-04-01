<!DOCTYPE html>
<html>
<body>
    <h2>Olá, {{ $asset->manager->name ?? 'Gestor' }}!</h2>
    <p>Um novo controlo de segurança foi implementado no ativo <strong>{{ $asset->name }}</strong>.</p>
    <ul>
        <li><strong>Controlo:</strong> {{ $control->name }}</li>
        <li><strong>Descrição:</strong> {{ $control->description }}</li>
    </ul>
    <p>Consulte o plano de tratamento de riscos para mais detalhes.</p>
</body>
</html>