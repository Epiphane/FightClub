<?

/*
 * Sort class file
 *
 * @author Thomas Steinke
 */
namespace Fight\Data;

class Sort
{
	public $property;
	public $direction = "ASC";

	public function __construct($property, $direction = 'ASC') 
	{
		$this->property = $property;

		if ($direction === "ASC" || $direction === "DESC") {
			$this->direction = $direction;
		}
	}
}
