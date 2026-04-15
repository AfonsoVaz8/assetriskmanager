<!DOCTYPE html>
<html>
<body>
    <h2>Hello, {{ $asset->manager->name ?? 'Gestor' }}!</h2>
    <p>A new threat has been associated with the asset <strong>{{ $asset->name }}</strong>.</p>
    <ul>
        <li><strong>Threat:</strong> {{ $threat->name }}</li>
        <li><strong>Description:</strong> {{ $threat->description }}</li>
    </ul>
    <p>Please, access the system to evaluate the impact and probability.</p>
</body>
</html>