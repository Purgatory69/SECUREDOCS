{pkgs}: {
  channel = "stable-24.05";
  packages = [
    pkgs.nodejs_20
    pkgs.php82
    pkgs.php82Packages.composer
    pkgs.python-launcher
    pkgs.python3
  ];
  idx.extensions = [
    "svelte.svelte-vscode"
    "vue.volar"
    "bmewburn.vscode-intelephense-client"
    "xdebug.php-debug"
  ];
  idx.previews = {
    previews = {
      web = {
        command = [
          "php"
          "artisan"
          "serve"
          "--host"
          "0.0.0.0"
          "--port"
          "$PORT"
        ];
        manager = "web";
      };
    };
  };
}