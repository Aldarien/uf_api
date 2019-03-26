<?php
namespace App\Service;

class Factory
{
  protected $model;
  protected $fields;
  protected $joins;
  protected $wheres;
  protected $orders;
  protected $groups;
  protected $limits;

  public function __construct($class_name) {
    if (!class_exists($class_name)) {
      throw new \InvalidArgumentException('Class ' . $class_name . ' not found.');
    }
    $this->model = $class_name;
  }
  public function new() {
    return \Model::factory($this->model)->new();
  }
  public function create(array $data) {
    return \Model::factory($this->model)->create($data);
  }
  public function find($many = false) {
    $orm = \Model::factory($this->model);
    $orm = $this->parseSelect($orm);
    $orm = $this->parseJoins($orm);
    $orm = $this->parseWhere($orm);
    $orm = $this->parseOrder($orm);
    $orm = $this->parseGroup($orm);
    $orm = $this->parseLimit($orm);
    if ($many) {
      return $orm->find_many();
    }
    return $orm->find_one();
  }
  protected function parseSelect($orm) {
    foreach ($this->selects as $select) {
      if (is_array($select)) {
        if (strpos($select[0], '(') !== false) {
          $orm->select_expr($select[0], $select[1]);
          continue;
        }
        $orm->select($select[0], $select[1]);
        continue;
      }
      if (strpos($select, '(') !== false) {
        $orm->select_expr($select);
        continue;
      }
      $orm->select($select);
      continue;
    }
    return $orm;
  }
  protected function parseJoins($orm) {
    $options = ['expr', 'left', 'inner', 'right', 'full'];
    $funcs = ['raw_', 'left_outer_', 'inner_', 'right_outer_', 'full_outer_'];
    foreach ($this->joins as $join) {
      $func = 'join';
      if (count($join) > 3) {
        $func .= $joins[array_search($options, strtolower($join[3]))];
      }
      if (isset($join[2])) {
        $orm->$func($join[0], $join[1], $join[2]);
        continue;
      }
      $orm->$func($join[0], $join[1]);
    }
    return $orm;
  }
  protected function parseWhere($orm) {
    $options = ['=', '!=', '<', '<=', '>', '>=', 'like', 'not like', 'expr',
      'in', 'not in', 'null', 'not'];
    $funcs = ['', '_not_equal', '_lt', '_lte', '_gt', '_gte', '_like',
      '_not_like', '_raw', '_in', '_not_in', '_null', '_not_null'];
    foreach ($this->wheres as $where) {
      if (count($where) < 3 and $where[1] != 'null') {
        $where[3] = '=';
      } elseif ($where[1] == 'null') {
        $where[3] = 'null';
      }
      $func = $funcs[array_search($options, strtolower($where[2]))];
      if ($func == '_null' or $func == '_not_null') {
        $orm->{'where' . $func}($where[0]);
        continue;
      }
      $orm->{'where' . $func}($where[0], $where[1]);
    }
    return $orm;
  }
  protected function parseOrder($orm) {
    return $orm;
  }
  protected function parseGroup($orm) {
    foreach ($this->orders as $field => $order) {
      if (!isset($field)) {
        $field = $order;
        $order = 'asc';
      }
      $orm->{'order_by_' . strtolower($order)}($field);
    }
    return $orm;
  }
  protected function parseLimit($orm) {
    return $orm;
  }
  public function where(array $wheres) {
    if ($this->wheres == null) {
      $this->wheres = [];
    }
    $this->wheres = array_merge($this->wheres, $wheres);
  }
  public function order(array $orders) {
    if ($this->orders = null) {
      $this->orders = [];
    }
    $this->orders = array_merge($this->orders, $orders);
  }
}
