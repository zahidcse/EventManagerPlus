<!DOCTYPE html>
<html class="h-full" lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
@include('partials.site-favicon')
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                primary: "#00288e",
                "primary-container": "#1e40af",
                "on-surface": "#191c1e",
                "on-surface-variant": "#444653",
                "surface-container-low": "#f3f4f6",
                "surface-container-lowest": "#ffffff",
                "outline-variant": "#c4c5d5",
                background: "#f8f9fb",
                outline: "#757684",
            },
        },
    },
};
</script>
<style>
.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
body { font-family: 'Inter', sans-serif; background-color: #f8f9fb; }
</style>
<title>@yield('title', 'Login') | Event Manager</title>
</head>
<body class="h-full flex flex-col justify-center items-center p-4">
@yield('content')
</body>
</html>
