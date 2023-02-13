<?php
// Actually, these things should be done by main_judger so that the code would be much simpler.
// However, this lib exists due to some history issues.

function dataNewProblem($id) {
	mkdir("/var/uoj_data/upload/$id");
	mkdir("/var/uoj_data/$id");
	mkdir(UOJContext::storagePath() . "/problem_resources/$id");

	UOJLocalRun::execAnd([
		['cd', '/var/uoj_data'],
		['rm', "$id.zip"],
		['zip', "$id.zip", $id, '-r', '-q']
	]);
}

function dataClearProblemData($problem) {
	$id = $problem['id'];
	if (!validateUInt($id)) {
		UOJLog::error("dataClearProblemData: hacker detected");
		return "invalid problem id";
	}

	UOJLocalRun::exec(['rm', "/var/uoj_data/$id", '-r']);
	UOJLocalRun::exec(['rm', "/var/uoj_data/upload/$id", '-r']);
	dataNewProblem($id);
}
