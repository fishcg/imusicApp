<?php
class Helper_Test
{
	static function test()
	{
		Session::meta()->table->insert(array(), false);
	}
}