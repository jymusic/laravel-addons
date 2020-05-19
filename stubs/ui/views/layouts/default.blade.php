<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
@yield('meta')
	<title>Addon {$addon_name}</title>
@stack('styles')
</head>

<body>
@yield('content')

@stack('scripts')
</body>
</html>
