# NodePHP

Monolith of PHP Node based programming.

Create new node from repo.
```bash
git clone https://<URL>/Kolostov/NodePHP.git .
```

Link existing git repo as new node Project:
```bash
php node git Project
git clone https://<URL>/Kolostov/EmptyRepo.git Git/Project
php node git Project
```

Run main application
```bash
php node serve
```

Defining node parameters in *node.json* file:
```json
{
    "name": "Sample",
    "run": "Function\\Helper\\FunctionName",
    "structure": [
        "Depricated" => "Files that are considered depricated.",
        "Log" => [
            "Internal" => "Application runtime logs",
            "Access" => "HTTP request logs",
            "Error" => "Error and exception logs",
            "Audit" => "Security and audit trails",
        ],
        "Git" => [
            "Node" => "Node.php project repository",
            "Project" => "All excluding the Node.php",
        ],
    ],
    "require": []
}
```

## TODO
* cli_make Git/Node/$symlink does not get removed after cloning from repo.
* this has to be excluded from checks if folder is empty or deleted after moving/making new symlink.
