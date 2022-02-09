<?php namespace Gecche\Breeze\Database\Schema;


trait Ownerships {


	/**
	 * Indicate that the ownerships columns should be dropped.
	 *
	 * @return void
	 */
	public function dropOwnerships()
	{
		$this->dropColumn('created_by', 'updated_by');
	}

	
	/**
	 * Create a new ownership column on the table.
	 *
	 * @param  string  $column
	 * @return \Illuminate\Support\Fluent
	 */
	public function ownership($column)
	{
		return $this->addColumn('integer', $column)->unsigned()->index();
	}

	/**
	 * Add nullable creation and update ownerships to the table.
	 *
	 * @return void
	 */
	public function nullableOwnerships()
	{
		$this->ownership('created_by')->nullable();

		$this->ownership('updated_by')->nullable();
	}

	/**
	 * Add creation and update ownerships to the table.
	 *
	 * @return void
	 */
	public function ownerships()
	{
		$this->ownership('created_by');

		$this->ownership('updated_by');
	}

    /**
     * Add a "deleted by" integer for the table.
     *
     * @param  string  $column
     * @return \Illuminate\Support\Fluent
     */
    public function softDeletesOwnerships($column = 'deleted_by')
    {
        return $this->integer($column)->unsigned()->nullable();
    }


}
