<?php

class Database
{
    private string $host;
    private string $port;
    private string $dbname;
    private string $user;
    private string $pass;

    public function __construct($host, $port, $dbname, $user, $pass)
    {
        $this->host = $host;
        $this->port = $port;
        $this->dbname = $dbname;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function connect(): PDO
    {
        $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";

        return new PDO($dsn, $this->user, $this->pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    }
}