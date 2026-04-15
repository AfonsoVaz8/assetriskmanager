<!DOCTYPE html>
<html>
<body>
    <h2>Hello, {{ $asset->manager->name ?? 'Gestor' }}!</h2>
    <p>A new asset has been assigned to you in the <strong>Risk Management System</strong>.</p>
    <ul>
        <li><strong>Name:</strong> {{ $asset->name }}</li>
        <li><strong>Type:</strong> {{ $asset->type->name ?? 'N/A' }}</li>
        <li><strong>IP:</strong> {{ $asset->ip_address ?? 'N/A' }}</li>
    </ul>
    <p>Please, access the system to view the details.</p>
</body>
</html>