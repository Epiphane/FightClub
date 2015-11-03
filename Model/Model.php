<?php
/*
 * Model class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Model;

use \Fight\Data\DAO;
use \Fight\Data\Request;
use \Fight\Data\Filter;

class Model
{
	public static $tableName;
	public static $pKey = null;
	public static $columns = [];
	public static $const_columns = [];

	public $_new = true;

	public static function build($assoc, $new = true) {
		$m = get_called_class();
		$model = new $m();
      $model->_new = $new;

		foreach($assoc as $col => $val) {
			$model->$col = $val;
		}

		if ($new) {
			$model->generatePrimaryKey();

			// COLLISION CHECK
			$runs = 1;
			while(call_user_func_array([$m, "findById"], $model->getPrimaryKeyValues())) {
				_log("Collision on Primary key " . $pKey . "=" .json_encode($model->getPrimaryKeyValues()));

				$model->generatePrimaryKey($runs++);

				if ($runs === 10) {
					_log("10 collisions, aborting..");

					throw new \Exception ("Primary Key collisions");
				}
			}
		}

		return $model;
	}

	public static function getPrimaryKey($m) {
		$key = $m::$pKey ?: $m::$const_columns[0];
		if (!$key) {
			reset($m::$columns);
			$key = key($m::$columns);
		}
		if (!is_array($key)) {
			$key = [$key];
		}
		return $key;
	}

	public function getPrimaryKeyValues() {
		$pKey = self::getPrimaryKey(get_called_class());

		$result = [];
		foreach ($pKey as $key) {
			$result[] = $this->$key;
		}

		return $result;
	}

	public function generatePrimaryKey($rerun = null) {
		$pKey = self::getPrimaryKey(get_called_class());

		foreach ($pKey as $key) {
			if (!$this->$key) {
				$method = "__gen_" . $key;
				if (is_callable($this->$method)) {
					$this->$key = $this->$method();
				}
				else {
					$this->$key = mt_rand(100000000, 999999999);
				}
			}
		}
	}

	public function save() {
		$model = get_called_class();
		$dao = new DAO($model);
	
		if ($this->_new) {
			return $dao->create($this);
		}
		else {
			return $dao->update($model, $this);
		}
	}

	public function read() {
		$result = [];

		$model = get_called_class();

		foreach($model::$columns as $colName => $type) {
			$result[$colName] = $this->$colName;
		}

		return $result;
	}

	public function update($attrs) {
		foreach(self::$const_columns as $key) {
			unset($attrs[$key]);
		}

		$dao = new DAO(get_called_class());

		return $dao->update($this, $attrs);
	}

	public static function findById($id) {
		$request = new Request();
		$pKey    = self::getPrimaryKey(get_called_class());
		foreach ($pKey as $index => $key) {
			$request->Filter[] = new Filter($key, func_get_arg($index));
		}

		return self::findOne($request);
	}

	public static function findOneWhere($properties) {
		$request = new Request();
		foreach ($properties as $property => $value) {
			$request->Filter[] = new Filter($property, $value);
		}

		return self::findOne($request);
	}

	public static function findWhere($properties) {
		$request = new Request();
		foreach ($properties as $property => $value) {
			$request->Filter[] = new Filter($property, $value);
		}

		return self::find($request);
	}

	public static function find($request) {
		$dao = new DAO(get_called_class());

		return $dao->find($request);
	}

	public static function findOne($request) {
		$dao = new DAO(get_called_class());

		return $dao->findOne($request);
	}
}
