<?

/*
 * InFilter class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Data;

class InFilter extends Filter
{
	public $property;
	public $comparator = 'IN';
	public $value;
	protected static $safe_comparisons = ['IN', 'NOT IN'];

	public function __construct($property, $values, $comparison = 'IN') 
	{
		$this->property   = $property;
		$this->value      = $values;
		if (in_array($comparison,self::$safe_comparisons))
			$this->comparator = $comparison;
	}
}
