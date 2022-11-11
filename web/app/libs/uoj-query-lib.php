<?php

function queryProblemBrief($id) {
	return DB::selectFirst("select * from problems where id = $id", MYSQLI_ASSOC);
}

function queryBlog($id) {
	return DB::selectFirst("select * from blogs where id='$id'", MYSQLI_ASSOC);
}
