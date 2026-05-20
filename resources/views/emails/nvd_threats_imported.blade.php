<!DOCTYPE html>
<html>
<body>
    <h2>Hello, {{ $asset->manager->name ?? 'Gestor' }}!</h2>
    <p>The synchronization with the NVD (National Vulnerability Database) found new threats associated with the asset <strong>{{ $asset->name }}</strong>.</p>

    <p>{{ count($threats) }} new vulnerability(ies) were added:</p>
    <ul>
        @foreach($threats as $threat)
            <li><strong>{{ $threat->name }}</strong>: {{ $threat->description }}</li>
        @endforeach
    </ul>

    <p>Please access the system to evaluate the impact of these threats and apply the appropriate controls.</p>
</body>
</html>