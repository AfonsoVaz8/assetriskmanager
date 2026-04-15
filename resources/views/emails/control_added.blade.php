<!DOCTYPE html>
<html>
<body>
    <h2>Hello, {{ $asset->manager->name ?? 'Gestor' }}!</h2>
    <p>A new security control has been implemented on the asset <strong>{{ $asset->name }}</strong>.</p>

    <p>This control was added for the threat: <strong>{{ $threat->name }}(id: {{ $threat->id }})</strong>.</p>

    <ul>
        <li><strong>Control:</strong> {{ $control->name }}</li>
        <li><strong>Description:</strong> {{ $control->description }}</li>
    </ul>
    <p>Consult the risk treatment plan for more details.</p>
</body>
</html>