<?php

namespace LRezek\Neo4PHP\Tests;
use Everyman\Neo4j\Cypher\Query as EM_QUERY;
use LRezek\Neo4PHP\Tests\Entity\FriendsWith;
use LRezek\Neo4PHP\Tests\Entity\User;

class RepositoryTest extends DatabaseTestCase
{
    //TODO: Query tests
    //TODO: Increase code coverage! Exception tests!

    function __construct()
    {
        //Generate a ID, so nodes can easily be found and deleted after tests
        $this->id = uniqid();

        //Get entity manager
        $em = $this->getEntityManager();

        //Create users
        $p1 = new User();
        $p2 = new User();
        $p3 = new User();
        $p4 = new User();
        $p5 = new User();

        //Brad -> 1990 -> Christian
        //Brad -> 1991 -> Scarlett
        //Brad -> 1992 -> Liam
        //Brad -> 1993 -> Ellen

        //Christian -> 1994 -> Brad
        //Christian -> 1995 -> Scarlett
        //Christian -> 1996 -> Liam
        //Christian -> 1997 -> Ellen

        //Scarlett -> 1998 -> Brad
        //Scarlett -> 1999 -> Christian
        //Scarlett -> 2000 -> Liam
        //Scarlett -> 2001 -> Ellen

        //Liam -> 2002 -> Brad
        //Liam -> 2003 -> Christian
        //Liam -> 2004 -> Scarlett
        //Liam -> 2005 -> Ellen

        //Ellen -> 2006 -> Brad
        //Ellen -> 2007 -> Christian
        //Ellen -> 2008 -> Scarlett
        //Ellen -> 2009 -> Liam

        //Write their properties
        $p1->setFirstName("Brad");
        $p1->setLastName("Pitt");
        $p1->setTestId($this->id);

        $p2->setFirstName("Christian");
        $p2->setLastName("Bale");
        $p2->setTestId($this->id);

        $p3->setFirstName("Scarlett");
        $p3->setLastName("Johansson");
        $p3->setTestId($this->id);

        $p4->setFirstName("Liam");
        $p4->setLastName("Neeson");
        $p4->setTestId($this->id);

        $p5->setFirstName("Ellen");
        $p5->setLastName("Page");
        $p5->setTestId($this->id);

        $nodes = array($p1, $p2, $p3, $p4, $p5);

        $year = 1990;

        //Create 20 relations
        $test_rels = array();
        for($i = 0; $i < 5; $i++)
        {
            $test_rels[$i] = array();

            for($j = 0; $j < 5; $j++)
            {
                if($j != $i)
                {
                    $test_rels[$i][$j] = new FriendsWith();
                    $test_rels[$i][$j]->setSince($year++);
                    $test_rels[$i][$j]->setFrom($nodes[$i]);
                    $test_rels[$i][$j]->setTo($nodes[$j]);
                    $em->persist($test_rels[$i][$j]);
                }
            }
        }

        $em->flush();

    }

    function __destruct()
    {
        $id = $this->id;
        $em = $this->getEntityManager();

        $queryString = "MATCH (n {testId:'$id'}) OPTIONAL MATCH (n)-[r]-() DELETE n,r";
        $query = new EM_QUERY($em->getClient(), $queryString);
        $result = $query->getResultSet();
    }

    //*****************************************************
    //***** FIND ONE TESTS ********************************
    //*****************************************************
    function testNodeFindOneByProperty()
    {
        $t = microtime(true);

        //Find a node
        $em = $this->getEntityManager();
        $repo = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\User');
        $user = $repo->findOneByFirstName('Brad');

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure the node is the right one
        $this->assertEquals("Brad", $user->getFirstName());
        $this->assertEquals("Pitt", $user->getLastName());
    }
    function testRelationFindOneByProperty()
    {
        $t = microtime(true);

        //Query for relation
        $em = $this->getEntityManager();
        $repo = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith');
        $rel = $repo->findOneBySince('1990');

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $start = $rel->getFrom();
        $end = $rel->getTo();

        //Validate the relation
        $this->assertEquals("1990", $rel->getSince());

        $this->assertEquals("Brad", $start->getFirstName());
        $this->assertEquals("Pitt", $start->getLastName());

        $this->assertEquals("Christian", $end->getFirstName());
        $this->assertEquals("Bale", $end->getLastName());
    }

    function testNodeFindOneByCriteria()
    {
        $t = microtime(true);

        //Find said node
        $repo = $this->getEntityManager()->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\User');
        $user = $repo->findOneBy(array("firstName" => 'Brad'));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Validate user
        $this->assertEquals("Brad", $user->getFirstName());
        $this->assertEquals("Pitt", $user->getLastName());

    }
    function testRelationFindOneByCriteria()
    {
        $t = microtime(true);

        //Query for relation
        $em = $this->getEntityManager();
        $repo = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith');
        $rel = $repo->findOneBy(array('since' => '1991'));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Validate relationship
        $start = $rel->getFrom();
        $end = $rel->getTo();

        $this->assertEquals("1991", $rel->getSince());

        $this->assertEquals("Brad", $start->getFirstName());
        $this->assertEquals("Pitt", $start->getLastName());

        $this->assertEquals("Scarlett", $end->getFirstName());
        $this->assertEquals("Johansson", $end->getLastName());

    }

    function testRelationFindOneByStartNodeProperty() {

        $em = $this->getEntityManager();

        //Find the Brad node
        $brad = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\User')->findOneByFirstName('Brad');

        $t = microtime(true);

        //Find his sibling relation
        $rel = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findOneByFrom($brad);

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $start = $rel->getFrom();
        $end = $rel->getTo();

        $this->assertEquals("Brad", $start->getFirstName());
        $this->assertEquals("Pitt", $start->getLastName());

        //The relation must be between years 1990 and 1994
        switch($rel->getSince())
        {
            //Christian Bale
            case 1990:
                $this->assertEquals("Christian", $end->getFirstName());
                $this->assertEquals("Bale", $end->getLastName());
                break;

            //Scarlet Johansson
            case 1991:
                $this->assertEquals("Scarlett", $end->getFirstName());
                $this->assertEquals("Johansson", $end->getLastName());
                break;

            //Liam Neeson
            case 1992:
                $this->assertEquals("Liam", $end->getFirstName());
                $this->assertEquals("Neeson", $end->getLastName());
                break;

            //Ellen Page
            case 1993:
                $this->assertEquals("Ellen", $end->getFirstName());
                $this->assertEquals("Page", $end->getLastName());
                break;

            default:
                $this->fail();
                break;
        }

    }
    function testRelationFindOneByEndNodeProperty() {

        //Find the christian node
        $em = $this->getEntityManager();
        $c = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\User')->findOneByFirstName('Christian');

        $t = microtime(true);

        //Find his sibling relation
        $rel = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findOneByTo($c);

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Grab relation start and end
        $start = $rel->getFrom();
        $end = $rel->getTo();

        //Validate the end node
        $this->assertEquals("Christian", $end->getFirstName());
        $this->assertEquals("Bale", $end->getLastName());

        //The relation must be in these years
        switch($rel->getSince())
        {
            //Brad Pitt
            case 1990:
                $this->assertEquals("Brad", $start->getFirstName());
                $this->assertEquals("Pitt", $start->getLastName());
                break;

            //Christian Bale
            case 1999:
                $this->assertEquals("Christian", $start->getFirstName());
                $this->assertEquals("Bale", $start->getLastName());
                break;

            //Liam Neeson
            case 2003:
                $this->assertEquals("Liam", $start->getFirstName());
                $this->assertEquals("Neeson", $start->getLastName());
                break;

            //Ellen Page
            case 2008:
                $this->assertEquals("Ellen", $start->getFirstName());
                $this->assertEquals("Page", $start->getLastName());
                break;

            default:
                $this->fail();
                break;
        }

    }

    function testNodeFindOneByWhenEmpty() {

        $em = $this->getEntityManager();

        $t = microtime(true);

        //The database is empty, do the query
        $user = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\User')->findOneByFirstName('Jennifer');

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there's nothing there
        $this->assertNull($user);
    }
    function testRelationFindOneByWhenEmpty() {

        $em = $this->getEntityManager();

        $t = microtime(true);

        //The database is empty, do the query
        $user = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findOneBySince('2050');

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there's nothing there
        $this->assertNull($user);

    }

    function testRelationFindOneByStartNodeCriteria() {

        $em = $this->getEntityManager();

        //Find the david node
        $brad = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\User')->findOneByFirstName('Brad');

        $t = microtime(true);

        //Find his sibling relation
        $rel = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findOneBy(array('from' => $brad));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $start = $rel->getFrom();
        $end = $rel->getTo();

        $this->assertEquals("Brad", $start->getFirstName());
        $this->assertEquals("Pitt", $start->getLastName());

        //The relation must be between years 1990 and 1994
        switch($rel->getSince())
        {
            //Christian Bale
            case 1990:
                $this->assertEquals("Christian", $end->getFirstName());
                $this->assertEquals("Bale", $end->getLastName());
                break;

            //Scarlet Johansson
            case 1991:
                $this->assertEquals("Scarlett", $end->getFirstName());
                $this->assertEquals("Johansson", $end->getLastName());
                break;

            //Liam Neeson
            case 1992:
                $this->assertEquals("Liam", $end->getFirstName());
                $this->assertEquals("Neeson", $end->getLastName());
                break;

            //Ellen Page
            case 1993:
                $this->assertEquals("Ellen", $end->getFirstName());
                $this->assertEquals("Page", $end->getLastName());
                break;

            default:
                $this->fail();
                break;
        }

    }
    function testRelationFindOneByEndNodeCriteria() {

        $em = $this->getEntityManager();

        //Find the christian node
        $c = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\User')->findOneByFirstName('Christian');

        $t = microtime(true);

        //Find his sibling relation
        $rel = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findOneBy(array('to' => $c));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Grab relation start and end
        $start = $rel->getFrom();
        $end = $rel->getTo();

        //Validate the end node
        $this->assertEquals("Christian", $end->getFirstName());
        $this->assertEquals("Bale", $end->getLastName());

        //The relation must be in these years
        switch($rel->getSince())
        {
            //Brad Pitt
            case 1990:
                $this->assertEquals("Brad", $start->getFirstName());
                $this->assertEquals("Pitt", $start->getLastName());
                break;

            //Christian Bale
            case 1999:
                $this->assertEquals("Christian", $start->getFirstName());
                $this->assertEquals("Bale", $start->getLastName());
                break;

            //Liam Neeson
            case 2003:
                $this->assertEquals("Liam", $start->getFirstName());
                $this->assertEquals("Neeson", $start->getLastName());
                break;

            //Ellen Page
            case 2008:
                $this->assertEquals("Ellen", $start->getFirstName());
                $this->assertEquals("Page", $start->getLastName());
                break;

            default:
                $this->fail();
                break;
        }

    }

    //*****************************************************
    //***** FIND BY TESTS *********************************
    //*****************************************************
    function testNodeFindByProperty() {

        $em = $this->getEntityManager();

        //Make 3 nodes
        for($i = 0; $i < 3; $i++)
        {
            $mov2 = new Entity\User;
            $mov2->setFirstName('Bradley');
            $mov2->setLastName("Cooper");
            $mov2->setTestId($this->id);
            $em->persist($mov2);
        }

        $em->flush();

        $t = microtime(true);

        //Find the 3 nodes
        $nodes = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\User')->findByFirstName("Bradley")->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there are 3 nodes
        $this->assertEquals(count($nodes), 3);

        //Make sure they contain the proper values
        foreach($nodes as $node)
        {
            $this->assertEquals($node->getFirstName(), "Bradley");
            $this->assertEquals($node->getLastName(), "Cooper");
        }

    }
    function testRelationFindByProperty()
    {

        $em = $this->getEntityManager();

        //Make 3 relations
        for($i = 0; $i < 3; $i++)
        {
            $mov1 = new Entity\User;
            $mov1->setFirstName('Dane');
            $mov1->setLastName('Cook');
            $mov1->setTestId($this->id);

            $mov2 = new Entity\User;
            $mov2->setFirstName('Chris');
            $mov2->setLastName('Tucker');
            $mov2->setTestId($this->id);

            $relation = new Entity\FriendsWith();
            $relation->setTo($mov1);
            $relation->setFrom($mov2);
            $relation->setSince("2050");

            $em->persist($relation);
        }

        $em->flush();

        $t = microtime(true);
        $relations = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findBySince("2050")->toArray();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there are 3 nodes
        $this->assertEquals(count($relations), 3);

        //Make sure they contain the proper values
        foreach($relations as $rel)
        {
            $this->assertEquals($rel->getSince(), "2050");
        }

    }

    function testNodeFindByCriteria() {

        $em = $this->getEntityManager();

        //Make 3 nodes
        for($i = 0; $i < 3; $i++)
        {
            $mov2 = new Entity\User;
            $mov2->setFirstName('Uma');
            $mov2->setLastName("Therman");
            $mov2->setTestId($this->id);
            $em->persist($mov2);
        }

        $em->flush();

        $t = microtime(true);

        //Find the 3 nodes
        $nodes = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\User')->findBy(array('firstName' => 'Uma', 'lastName' => 'Therman'))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there are 3 nodes
        $this->assertEquals(count($nodes), 3);

        //Make sure they contain the proper values
        foreach($nodes as $node)
        {
            $this->assertEquals($node->getFirstName(), "Uma");
            $this->assertEquals($node->getLastName(), "Therman");
        }

    }
    function testRelationFindByCriteria()
    {

        $em = $this->getEntityManager();

        //Make 3 relations
        for($i = 0; $i < 3; $i++)
        {
            $mov1 = new Entity\User;
            $mov1->setFirstName('Will');
            $mov1->setLastName('Smith');
            $mov1->setTestId($this->id);

            $mov2 = new Entity\User;
            $mov2->setFirstName('Michael');
            $mov2->setLastName('Cera');
            $mov2->setTestId($this->id);

            $relation = new Entity\FriendsWith();
            $relation->setTo($mov1);
            $relation->setFrom($mov2);
            $relation->setSince("2051");

            $em->persist($relation);
        }


        $em->flush();

        $t = microtime(true);

        $relations = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findBy(array('since' => '2051'))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there are 3 nodes
        $this->assertEquals(count($relations), 3);

        //Make sure they contain the proper values
        foreach($relations as $rel)
        {
            $this->assertEquals($rel->getSince(), "2051");
        }

    }

    function testNodeFindByWhenEmpty() {

        $em = $this->getEntityManager();

        $t = microtime(true);

        //Do the query
        $users = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\User')->findBy(array('firstName' => 'Martha'))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there's nothing there
        $this->assertEquals(count($users), 0);
    }
    function testRelationFindByWhenEmpty() {

        $em = $this->getEntityManager();

        $t = microtime(true);

        //Do the query
        $users = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findBy(array('since' => '3000'))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there's nothing there
        $this->assertEquals(count($users), 0);

    }

    function testRelationFindByStartNodeProperty() {

        $em = $this->getEntityManager();

        $mov1 = new Entity\User;
        $mov1->setFirstName('Charlie');
        $mov1->setLastName('Sheen');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\User;
        $mov2->setFirstName('Jamie');
        $mov2->setLastName('Foxx');
        $mov2->setTestId($this->id);

        $rel1 = new Entity\FriendsWith();
        $rel1->setTo($mov1);
        $rel1->setFrom($mov2);
        $rel1->setSince("2052");

        $rel2 = new Entity\FriendsWith();
        $rel2->setTo($mov1);
        $rel2->setFrom($mov2);
        $rel2->setSince("2053");

        $em->persist($rel1);
        $em->persist($rel2);
        $em->flush();

        //Grab the Jamie node
        $jam = $em->reload($mov2);

        $t = microtime(true);
        $rels = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findByFrom($jam)->toArray();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($rels), 2);

    }
    function testRelationFindByEndNodeProperty() {

        $em = $this->getEntityManager();

        $mov1 = new Entity\User;
        $mov1->setFirstName('Jerry');
        $mov1->setLastName('Seinfeld');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\User;
        $mov2->setFirstName('Clint');
        $mov2->setLastName('Eastwood');
        $mov2->setTestId($this->id);

        $rel1 = new Entity\FriendsWith();
        $rel1->setTo($mov1);
        $rel1->setFrom($mov2);
        $rel1->setSince("2054");

        $rel2 = new Entity\FriendsWith();
        $rel2->setTo($mov1);
        $rel2->setFrom($mov2);
        $rel2->setSince("2055");

        $em->persist($rel1);
        $em->persist($rel2);
        $em->flush();

        //Grab jerry
        $jerry = $em->reload($mov1);

        $t = microtime(true);
        $rels = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findByTo($jerry)->toArray();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($rels), 2);

    }

    function testRelationFindByStartNodeCriteria() {

        $em = $this->getEntityManager();

        $mov1 = new Entity\User;
        $mov1->setFirstName('Alec');
        $mov1->setLastName('Baldwin');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\User;
        $mov2->setFirstName('John');
        $mov2->setLastName('Travolta');
        $mov2->setTestId($this->id);

        $rel1 = new Entity\FriendsWith();
        $rel1->setTo($mov1);
        $rel1->setFrom($mov2);
        $rel1->setSince("2056");

        $rel2 = new Entity\FriendsWith();
        $rel2->setTo($mov1);
        $rel2->setFrom($mov2);
        $rel2->setSince("2057");

        $em->persist($rel1);
        $em->persist($rel2);
        $em->flush();

        //Grab the john node
        $john = $em->reload($mov2);

        $t = microtime(true);

        //Find his sibling relations
        $rels = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findBy(array('from' => $john))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($rels), 2);

    }
    function testRelationFindByEndNodeCriteria() {

        $em = $this->getEntityManager();

        $mov1 = new Entity\User;
        $mov1->setFirstName('Simon');
        $mov1->setLastName('Cowell');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\User;
        $mov2->setFirstName('Tiger');
        $mov2->setLastName('Woods');
        $mov2->setTestId($this->id);

        $rel1 = new Entity\FriendsWith();
        $rel1->setTo($mov1);
        $rel1->setFrom($mov2);
        $rel1->setSince("2058");

        $rel2 = new Entity\FriendsWith();
        $rel2->setTo($mov1);
        $rel2->setFrom($mov2);
        $rel2->setSince("2059");

        $em->persist($rel1);
        $em->persist($rel2);
        $em->flush();

        //Grab simon
        $simon = $em->reload($mov1);

        $t = microtime(true);

        //Find his sibling relation
        $rels = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\FriendsWith')->findBy(array('to' => $simon))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($rels), 2);

    }

    //*****************************************************
    //***** FIND ALL TESTS ********************************
    //*****************************************************
    function testNodeFindAll() {

        //Create nodes
        $mov1 = new Entity\Person();
        $mov1->setFirstName('Orlando');
        $mov1->setLastName('Bloom');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\Person();
        $mov2->setFirstName('Mila');
        $mov2->setLastName('Kunis');
        $mov2->setTestId($this->id);

        $em = $this->getEntityManager();
        $em->persist($mov1);
        $em->persist($mov2);
        $em->flush();

        $t = microtime(true);
        $nodes = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\Person')->findAll()->toArray();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($nodes), 2);
    }
    function testRelationFindAll() {

        $mov1 = new Entity\User();
        $mov1->setFirstName('Owen');
        $mov1->setLastName('Wilson');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\User();
        $mov2->setFirstName('Morgan');
        $mov2->setLastName('Freeman');
        $mov2->setTestId($this->id);

        $mov3 = new Entity\User();
        $mov3->setFirstName('Katy');
        $mov3->setLastName('Perry');
        $mov3->setTestId($this->id);

        //Create relation from David to Lukas
        $relation = new Entity\Likes();
        $relation->setTo($mov1);
        $relation->setFrom($mov2);
        $relation->setSince("2060");

        //Create relation from David to Nicole
        $relation2 = new Entity\Likes();
        $relation2->setTo($mov3);
        $relation2->setFrom($mov2);
        $relation2->setSince("2061");

        $em = $this->getEntityManager();
        $em->persist($relation);
        $em->persist($relation2);
        $em->flush();

        $t = microtime(true);

        $rels = $em->getRepository('LRezek\\Neo4PHP\\Tests\\Entity\\Likes')->findAll()->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($rels), 2);

    }

}