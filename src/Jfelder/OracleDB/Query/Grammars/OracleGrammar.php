<?php namespace Jfelder\OracleDB\Query\Grammars;

use \Illuminate\Database\Query\Builder;
use Config;

class OracleGrammar extends \Illuminate\Database\Query\Grammars\Grammar {

	/**
	 * Compile a select query into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder
	 * @return string
	 */
	public function compileSelect(Builder $query)
	{
		if (is_null($query->columns)) $query->columns = array('*');

		$components = $this->compileComponents($query);

		// If an offset is present on the query, we will need to wrap the query in
		// a big "ANSI" offset syntax block. This is very nasty compared to the
		// other database systems but is necessary for implementing features.
		if ($query->limit > 0 || $query->offset > 0)
		{
			return $this->compileAnsiOffset($query, $components);
		}

		return trim($this->concatenate($components));
	}

	/**
	 * Compile an insert and get ID statement into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array   $values
	 * @param  string  $sequence
	 * @return string
	 */
	public function compileInsertGetId(Builder $query, $values, $sequence)
	{
		if (is_null($sequence)) $sequence = 'id';

		return $this->compileInsert($query, $values).' returning '.$this->wrap($sequence).' into ?';
	}
        
        /**
	 * Compile the lock into SQL.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  bool|string  $value
	 * @return string
	 */
	protected function compileLock(Builder $query, $value)
	{
		if (is_string($value)) return $value;

		return $value ? 'for update' : 'lock in share mode';
	}


	/**
	 * Create a full ANSI offset clause for the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  array  $components
	 * @return string
	 */
	protected function compileAnsiOffset(Builder $query, $components)
	{
		$constraint = $this->compileRowConstraint($query);

		$sql = $this->concatenate($components);

		// We are now ready to build the final SQL query so we'll create a common table
		// expression from the query and get the records with row numbers within our
		// given limit and offset value that we just put on as a query constraint.
		$temp = $this->compileTableExpression($sql, $constraint, $query);
                
                return $temp;
	}

	/**
	 * Compile the limit / offset row constraint for a query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return string
	 */
	protected function compileRowConstraint($query)
	{
		$start = $query->offset + 1;

		if ($query->limit > 0)
		{
			$finish = $query->offset + $query->limit;

			return "between {$start} and {$finish}";
		}
	
		return ">= {$start}";
	}

	/**
	 * Compile a common table expression for a query.
	 *
	 * @param  string  $sql
	 * @param  string  $constraint
	 * @return string
         * 
 	 */
	protected function compileTableExpression($sql, $constraint, $query)
	{
            if ($query->limit > 0) {
                return "select t2.* from ( select rownum AS \"rn\", t1.* from ({$sql}) t1 ) t2 where t2.\"rn\" {$constraint}";
            } else {
                return "select * from ({$sql}) where rownum {$constraint}";
            }
	}

	/**
	 * Compile the "limit" portions of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  int  $limit
	 * @return string
	 */
	protected function compileLimit(Builder $query, $limit)
	{
		return '';
	}

	/**
	 * Compile the "offset" portions of the query.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @param  int  $offset
	 * @return string
	 */
	protected function compileOffset(Builder $query, $offset)
	{
		return '';
	}

	/**
 	 * Wrap a single string in keyword identifiers.
 	 *
 	 * @param  string  $value
 	 * @return string
 	 */
 	protected function wrapValue($value)
 	{
 		if (Config::get('oracledb::database.quoting') === true) {
 			return parent::wrapValue($value);
 		} 

 		return $value;
 	}
 
}
