<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Offset \'cid\' might not exist on array\\{0\\?\\: string, cid\\?\\: numeric\\-string, 1\\?\\: numeric\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/DrupalOrg/IssueGraph/DrupalOrgIssueGraph.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'project\' might not exist on array\\{0\\?\\: string, project\\?\\: string, 1\\?\\: string, mr_id\\: numeric\\-string, 2\\?\\: numeric\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/DrupalOrg/IssueGraph/DrupalOrgIssueGraph.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'sequence\' might not exist on array\\{0\\?\\: string, sequence\\?\\: numeric\\-string, 1\\?\\: numeric\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/DrupalOrg/IssueGraph/DrupalOrgIssueGraph.php',
];
$ignoreErrors[] = [
	'message' => '#^Property dogit\\\\DrupalOrg\\\\IssueGraph\\\\DrupalOrgIssueGraph\\:\\:\\$browser with generic class Symfony\\\\Component\\\\BrowserKit\\\\AbstractBrowser does not specify its types\\: TRequest, TResponse$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/src/DrupalOrg/IssueGraph/DrupalOrgIssueGraph.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function array_filter\\(\\) requires parameter \\#2 to be passed to avoid loose comparison semantics\\.$#',
	'identifier' => 'arrayFilter.strict',
	'count' => 2,
	'path' => __DIR__ . '/src/DrupalOrg/Objects/DrupalOrgIssue.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function array_filter\\(\\) requires parameter \\#2 to be passed to avoid loose comparison semantics\\.$#',
	'identifier' => 'arrayFilter.strict',
	'count' => 1,
	'path' => __DIR__ . '/src/Git/GitOperator.php',
];
$ignoreErrors[] = [
	'message' => '#^Class dogit\\\\HttplugBrowser extends generic class Symfony\\\\Component\\\\BrowserKit\\\\AbstractBrowser but does not specify its types\\: TRequest, TResponse$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/src/HttplugBrowser.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison via "\\=\\=" is not allowed\\.$#',
	'identifier' => 'equal.notAllowed',
	'count' => 1,
	'path' => __DIR__ . '/src/Listeners/PatchToBranch/Filter/ByMetadata.php',
];
$ignoreErrors[] = [
	'message' => '#^Loose comparison via "\\=\\=" is not allowed\\.$#',
	'identifier' => 'equal.notAllowed',
	'count' => 1,
	'path' => __DIR__ . '/src/Listeners/PatchToBranch/Version/ByTestResultsEvent.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to __construct\\(\\) on an existing object is not allowed\\.$#',
	'identifier' => 'constructor.call',
	'count' => 2,
	'path' => __DIR__ . '/tests/Commands/IssueMergeRequestTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to __construct\\(\\) on an existing object is not allowed\\.$#',
	'identifier' => 'constructor.call',
	'count' => 1,
	'path' => __DIR__ . '/tests/Commands/PatchToBranchTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to __construct\\(\\) on an existing object is not allowed\\.$#',
	'identifier' => 'constructor.call',
	'count' => 3,
	'path' => __DIR__ . '/tests/Commands/ProjectCloneCommandTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to __construct\\(\\) on an existing object is not allowed\\.$#',
	'identifier' => 'constructor.call',
	'count' => 6,
	'path' => __DIR__ . '/tests/Commands/ProjectMergeRequestTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'cid\' might not exist on array\\{0\\?\\: string, cid\\?\\: numeric\\-string, 1\\?\\: numeric\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/tests/DogitGuzzleTestMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'fid\' might not exist on array\\{0\\?\\: string, fid\\?\\: numeric\\-string, 1\\?\\: numeric\\-string\\}\\.$#',
	'identifier' => 'offsetAccess.notFound',
	'count' => 1,
	'path' => __DIR__ . '/tests/DogitGuzzleTestMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertCount\\(\\) with 2 and array\\{dogit\\\\DrupalOrg\\\\Objects\\\\DrupalOrgIssue, dogit\\\\DrupalOrg\\\\Objects\\\\DrupalOrgIssue, dogit\\\\DrupalOrg\\\\Objects\\\\DrupalOrgIssue\\} will always evaluate to false\\.$#',
	'identifier' => 'method.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/tests/DrupalOrg/DrupalOrgObjectCollectionTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method PHPUnit\\\\Framework\\\\Assert\\:\\:assertCount\\(\\) with 2 and array\\{dogit\\\\DrupalOrg\\\\Objects\\\\DrupalOrgObject, dogit\\\\DrupalOrg\\\\Objects\\\\DrupalOrgObject, dogit\\\\DrupalOrg\\\\Objects\\\\DrupalOrgObject\\} will always evaluate to false\\.$#',
	'identifier' => 'method.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/tests/DrupalOrg/DrupalOrgObjectRepositoryTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Http\\\\Promise\\\\Promise\\|Prophecy\\\\Prophecy\\\\MethodProphecy\\:\\:willReturn\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 2,
	'path' => __DIR__ . '/tests/UtilityTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method dogit\\\\DrupalOrg\\\\DrupalApiInterface\\|Prophecy\\\\Prophecy\\\\ObjectProphecy\\:\\:reveal\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/tests/UtilityTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var for variable \\$api contains generic class Prophecy\\\\Prophecy\\\\ObjectProphecy but does not specify its types\\: T$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/tests/UtilityTest.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type dogit\\\\DrupalOrg\\\\DrupalApiInterface\\|Prophecy\\\\Prophecy\\\\ObjectProphecy is not subtype of native type Prophecy\\\\Prophecy\\\\ObjectProphecy\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/tests/UtilityTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$api of class dogit\\\\DrupalOrg\\\\DrupalOrgObjectIterator constructor expects dogit\\\\DrupalOrg\\\\DrupalApiInterface, object given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/UtilityTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$comment of method dogit\\\\DrupalOrg\\\\DrupalApiInterface\\:\\:getCommentAsync\\(\\) expects dogit\\\\DrupalOrg\\\\Objects\\\\DrupalOrgComment, Prophecy\\\\Argument\\\\Token\\\\AnyValuesToken given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/UtilityTest.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$file of method dogit\\\\DrupalOrg\\\\DrupalApiInterface\\:\\:getFileAsync\\(\\) expects dogit\\\\DrupalOrg\\\\Objects\\\\DrupalOrgFile, Prophecy\\\\Argument\\\\Token\\\\AnyValuesToken given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/tests/UtilityTest.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
