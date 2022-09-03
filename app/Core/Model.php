<?php

namespace App\Core;

use Exception;
use PDO;
use PDOException;
use stdClass;

abstract class Model
{

    /** @var string $entity Nome da tabela */
    private string $entity;

    /** @var string $primary Chave primária, simples ou composta */
    private string $primary;

    /** @var array $entity Colunas obrigatórias */
    private array $required;

    /** @var string $query SQL Statement a ser executada */
    protected string $query = '';

    /** @var string $where Condição where da consulta */
    private string $where = '';

    /** @var array $params Parâmetros */
    protected array $params = [];

    /** @var string $joins Joins entre tabelas */
    protected string $joins = '';

    /** @var string $group */
    protected string $group = '';

    /** @var string */
    protected string $order = '';

    /** @var string */
    protected string $limit = '';

    /** @var string */
    protected string $offset = '';

    /** @var Exception|PDOException|null */
    protected Exception|null|PDOException $fail = null;

    /** @var null|string */
    protected ?string $message = null;

    /** @var object|null $data Dados da tabela */
    protected ?object $data = null;

    protected array $protected = [];

    /**
     * @param string $entity Nome da tabela
     * @param array $required Campos obrigatórios
     * @param array $protected
     * @param string $primary
     */
    public function __construct(string $entity, array $required, array $protected, string $primary = 'id')
    {
        $this->entity = $entity;
        $this->required = $required;
        $this->protected = $protected;
        $this->primary = $primary;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (empty($this->data)) {
            $this->data = new stdClass();
        }

        $this->data->$name = $value;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data->$name);
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        return ($this->data->$name ?? null);
    }

    /**
     * @return null|object
     */
    public function data(): ?object
    {
        return $this->data;
    }

    /**
     * @return Exception|PDOException|null
     */
    public function fail(): PDOException|Exception|null
    {
        return $this->fail;
    }

    /**
     * @return string|null
     */
    public function message(): ?string
    {
        return $this->message;
    }

    /**
     * @return string Nome da tabela
     */
    public function entity(): string
    {
        return $this->entity;
    }

    /**
     * @param string|null $terms
     * @param string|null $params
     * @param string $columns
     * @return Model
     */
    public function find(?string $terms = null, ?string $params = null, string $columns = "*"): Model
    {
        $this->query = "SELECT $columns FROM $this->entity";

        if ($terms) {
            $this->where = " WHERE $terms";
            parse_str($params, $this->params);
            return $this;
        }

        $this->where = "";
        return $this;
    }

    /**
     * @param int|string $primaryKey
     * @param string $columns
     * @return null | Model | mixed
     */
    protected function findByPrimaryKey(int|string $primaryKey, string $columns = "*"): mixed
    {
        return $this->find("$this->primary = :primary", "primary=$primaryKey", $columns)->fetch();
    }

    /**
     * @param string $table [obrigatório] Nome da tabela
     * @param string $first [obrigatório] Nome da tabela e coluna com chave estrangeira. Ex: 'usuarios.ex_arquivo'
     * @param string|null $operator [opcional] Operador. Ex: '=', '>=', '!=', 'IS NULL'
     * @param string|null $second [opcional] Nome da tabela e coluna com a chave primária referenciada. Ex: arquivos.registro
     * @param string $type [opcional] Tipo de join. Ex: INNER, LEFT, RIGHT, FULL OUTER. Valor padrão = 'INNER'
     * @param string|null $where [opcional] Condição complementar para o join
     * @return Model
     */
    public function join(
        string  $table,
        string  $first,
        ?string $operator = null,
        ?string $second = null,
        string  $type = 'inner',
        ?string $where = null
    ): Model
    {
        if (empty($this->joins)) {
            $this->joins = "";
        }

        $type = strtoupper($type);
        $where = (!empty($where) ? ' AND ' . $where : "");

        $this->joins .= " $type JOIN $table ON $first $operator {$second}{$where}";
        return $this;
    }

    /**
     * @param string $column
     * @return Model
     */
    public function group(string $column): Model
    {
        $this->group = " GROUP BY $column";
        return $this;
    }

    /**
     * @param string $columnOrder [obrigatório] Coluna para ordenação. Ex: 'datahora_cadastro DESC'
     * @return Model
     */
    public function order(string $columnOrder): Model
    {
        $this->order = " ORDER BY $columnOrder";
        return $this;
    }

    /**
     *
     * @param int $limit
     * @return Model
     */
    public function limit(int $limit): Model
    {
        $this->limit = " LIMIT $limit";
        return $this;
    }

    /**
     *
     * @param int $offset
     * @return Model
     */
    public function offset(int $offset): Model
    {
        $this->offset = " OFFSET $offset";
        return $this;
    }

    /**
     * @param string $query
     * @param array|null $bindValues
     * @param bool $all
     * @return array|false|mixed|object|stdClass|null
     */
    public static function fullQuery(string $query, ?array $bindValues = null, bool $all = true): mixed
    {
        $filter = [];
        if (!empty($bindValues)) {
            foreach ($bindValues as $key => $value) {
                $filter[$key] = (is_null($value) ? null : filter_var($value));
            }
        }

        try {
            $stmt = Connect::getInstance()->prepare($query);
            $stmt->execute($filter);

            if ($all) {
                return $stmt->fetchAll();
            }
            return $stmt->fetchObject();
        } catch (PDOException) {
            return null;
        }
    }

    /**
     *
     * @param bool $all
     * @return array|Model|null Description
     */
    public function fetch(bool $all = false): mixed
    {
        try {
            $stmt = Connect::getInstance()->prepare(
                $this->query .
                $this->joins .
                $this->where .
                $this->group .
                $this->order .
                $this->limit .
                $this->offset
            );
            $stmt->execute($this->params);

            if (!$stmt->rowCount()) {
                return null;
            }

            if ($all) {
                return $stmt->fetchAll(PDO::FETCH_CLASS, get_called_class());
            }

            return $stmt->fetchObject(get_called_class());
        } catch (PDOException $exception) {
            $this->fail = $exception;
            $this->message = "Erro ao buscar os dados";
            return null;
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        try {
            $stmt = Connect::getInstance()->prepare($this->query);
            $stmt->execute($this->params);

            return $stmt->rowCount();
        } catch (PDOException $exception) {
            $this->fail = $exception;
            $this->message = "Erro ao buscar os dados";
            return 0;
        }
    }

    public function save(): bool
    {
        if (!$this->required()) {
            $this->fail = new Exception('Preencha todos os campos necessários');
            $this->message = "Preencha todos os campos necessários";
            return false;
        }

        $primary = $this->primary;

        if (empty($this->$primary)) {
            $primaryValue = $this->create($this->safe());
            if ($this->fail()) {
                return false;
            }
        }

        if (!empty($this->$primary)) {
            $primaryValue = $this->$primary;
            $this->update($this->safe(), "$this->primary = :primary", "primary={$primaryValue}");
            if ($this->fail()) {
                return false;
            }
        }

        if (empty($primaryValue)) {
            return false;
        }

        $this->data = $this->findByPrimaryKey($primaryValue)->data();
        return true;
    }

    /**
     * @param array $data
     * @return int|bool
     */
    protected function create(array $data): int|bool
    {
        try {
            $columns = implode(", ", array_keys($data));
            $values = ":" . implode(", :", array_keys($data));

            $stmt = Connect::getInstance()->prepare("INSERT INTO $this->entity ($columns) VALUES ($values)");
            if ($stmt->execute($this->filter($data))) {
                return Connect::getInstance()->lastInsertId();
            }
            return false;
        } catch (PDOException $exception) {
            $this->fail = $exception;
            $this->message = "Houve um erro ao cadastrar";
            return false;
        }
    }

    /**
     *
     * @param array $data
     * @param string $terms
     * @param string $params
     * @return int|null
     */
    protected function update(array $data, string $terms, string $params): ?int
    {
        try {
            $dateSet = [];
            foreach ($data as $bind => $value) {
                $dateSet[] = "$bind = :$bind";
            }
            $dateSet = implode(", ", $dateSet);
            parse_str($params, $this->params);

            $stmt = Connect::getInstance()->prepare("UPDATE $this->entity SET $dateSet WHERE $terms");
            $stmt->execute($this->filter(array_merge($data, $this->params)));
            return ($stmt->rowCount() ?? 1);
        } catch (PDOException $exception) {
            $this->fail = $exception;
            $this->message = "Houve um erro ao atualizar";
            return null;
        }
    }

    /**
     *
     * @param string $terms
     * @param string|null $params
     * @return bool
     */
    public function delete(string $terms, ?string $params): bool
    {
        try {
            $stmt = Connect::getInstance()->prepare("DELETE FROM $this->entity WHERE $terms");
            if ($params) {
                parse_str($params, $this->params);
                $stmt->execute($this->params);
                return true;
            }
            $stmt->execute();
            return true;
        } catch (PDOException $exception) {
            $this->fail = $exception;
            $this->message = "Houve um erro ao deletar";
            return false;
        }
    }


    /**
     *
     * @return bool
     */
    public function destroy(): bool
    {
        if (empty($this->primary)) {
            return false;
        }

        $primary = $this->primary;
        $where = "$primary = :primary";
        $params = "primary={$this->data->$primary}";


        return $this->delete($where, $params);
    }

    /**
     * @return array|null
     */
    protected function safe(): ?array
    {
        $safe = (array)$this->data;
        foreach ($this->protected as $unset) {
            unset($safe[$unset]);
        }
        return $safe;
    }

    /**
     * @param array $data
     * @return array|null
     */
    private function filter(array $data): ?array
    {
        $filter = [];
        foreach ($data as $key => $value) {
            $filter[$key] = (is_null($value) ? null : filter_var($value));
        }
        return $filter;
    }

    /**
     * @return bool
     */
    protected function required(): bool
    {
        $data = (array)$this->data();
        foreach ($this->required as $field) {
            if (empty($data[$field])) {
                if (!is_int($data[$field])) {
                    return false;
                }
            }
        }
        return true;
    }

}

