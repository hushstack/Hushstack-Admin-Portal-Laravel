# Gemini CLI MCP Setup

This document explains how to connect the Hushstack Laravel project with Gemini CLI using MCP (Model Context Protocol).

## Prerequisites

1. Install Gemini CLI:
```bash
npm install -g @google/gemini-cli
```

2. Verify installation:
```bash
gemini --version
```

## Setup Instructions

### Step 1: Update Project Path

Edit `.gemini/mcp-config.json` and replace `{PROJECT_PATH}` with your actual project path:

```json
{
  "mcpServers": {
    "hushstack-laravel": {
      "command": "php",
      "args": [
        "D:/HushStuck_FreelanceTeam/Hushstack-Admin-Portal-Laravel/artisan",
        "mcp:serve"
      ],
      "env": {
        "APP_ENV": "local",
        "APP_DEBUG": "true"
      }
    }
  }
}
```

### Step 2: Configure Gemini CLI

#### Option A: Global Configuration (Recommended)

Create or edit the global Gemini config file:

**Windows:**
```bash
# Location: %APPDATA%\gemini\settings.json
mkdir "%APPDATA%\gemini"
```

**Mac/Linux:**
```bash
mkdir -p ~/.config/gemini
```

Add the MCP configuration:

```json
{
  "mcpServers": {
    "hushstack-laravel": {
      "command": "php",
      "args": [
        "D:/HushStuck_FreelanceTeam/Hushstack-Admin-Portal-Laravel/artisan",
        "mcp:serve"
      ],
      "env": {
        "APP_ENV": "local"
      }
    }
  }
}
```

#### Option B: Project-Local Configuration

Run Gemini CLI from the project directory with the config:

```bash
cd D:/HushStuck_FreelanceTeam/Hushstack-Admin-Portal-Laravel
gemini --mcp-config .gemini/mcp-config.json
```

### Step 3: Test the Connection

1. Test the MCP server:
```bash
php artisan mcp:serve
```

2. In another terminal, run Gemini CLI:
```bash
gemini
```

3. Ask Gemini about your project:
```
> What files are in my Laravel project?
> Run the migrate status command
> Show me the API routes
```

## Available MCP Tools

Once connected, Gemini CLI can use these tools:

### 1. `run_artisan_command`
Run any Laravel Artisan command:
```
> Run artisan route:list
> Run artisan migrate:status
```

### 2. `get_project_structure`
Get the Laravel project directory structure:
```
> Show me the project structure
> What directories are in my app folder?
```

### 3. `read_file`
Read any file from the project:
```
> Read the file routes/api.php
> Show me config/app.php
> What's in the User model?
```

## Troubleshooting

### Issue: "php is not recognized"
**Solution:** Ensure PHP is in your system PATH, or use the full path to php.exe:
```json
{
  "command": "C:/php/php.exe",
  "args": [
    "D:/HushStuck_FreelanceTeam/Hushstack-Admin-Portal-Laravel/artisan",
    "mcp:serve"
  ]
}
```

### Issue: "Connection refused"
**Solution:** Make sure the MCP server is running:
```bash
php artisan mcp:serve
```

### Issue: "Permission denied"
**Solution:** Run your terminal as administrator, or check file permissions.

## Security Considerations

- The MCP server runs with local environment privileges
- File access is limited to the project directory
- Large files (>1MB) are blocked from reading
- Only read operations are allowed through MCP tools

## Additional Resources

- [Gemini CLI Documentation](https://github.com/google-gemini/gemini-cli)
- [MCP Protocol Specification](https://modelcontextprotocol.io/)
- [Laravel Artisan Documentation](https://laravel.com/docs/artisan)
