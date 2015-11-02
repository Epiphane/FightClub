<?

/*
 * Filter class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Data;

class Filter
{
	public $property;
	public $comparator = '=';
	public $value;
	protected static $safe_comparisons = ['=', '<=', '>=', '!=', 'IS', 'IS NOT', 'IN', 'NOT IN', 'LIKE'];

	public function __construct($property, $value, $comparison = '=') 
	{
		$this->property   = $property;
		$this->value      = $value;
		if (in_array($comparison,self::$safe_comparisons))
			$this->comparator = $comparison;
	}
}
