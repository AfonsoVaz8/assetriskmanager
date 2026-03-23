<!DOCTYPE html>
<html>
<body>
    <h2>Olá, {{ $asset->manager->name ?? 'Gestor' }}!</h2>
    <p>Foi-lhe atribuído um novo ativo no <strong>Sistema de Gestão de Risco</strong>.</p>
    <ul>
        <li><strong>Nome:</strong> {{ $asset->name }}</li>
        <li><strong>Tipo:</strong> {{ $asset->type->name ?? 'N/A' }}</li>
        <li><strong>IP:</strong> {{ $asset->ip_address ?? 'N/A' }}</li>
    </ul>
    <p>Por favor, aceda ao sistema para consultar os detalhes.</p>
</body>
</html>