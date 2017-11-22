<?php
namespace App\Controller;

use Aura\Intl\Exception;
use Eventviva\ImageResize;

/**
 * Images Controller
 *
 *
 * @method \App\Model\Entity\Image[] paginate($object = null, array $settings = [])
 */
class ImagesController extends AppController
{
    private $bracketCount = 0;
    private $delimiter = array('and', 'or', '(', ')');

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     *
     *
     */
    public function index()
    {
        $images = $this->paginate($this->Images, ['limit' => 4]);
        $this->set(compact('images'));
    }

    /**
     * Parse method
     *
     * @return \Cake\Http\Response|null
     * Return json if ajax and die if get from url (to see the result in the php array).
     */
    public function parse()
    {
        //To see result from ../images/parse and change query from code
        //$this->request->allowMethod(array('ajax'));


        if ($this->request->is('post')) {
            try {
                $query = $this->request->getData()['query'];
                if (!empty($query)) {
                    $query = strtolower($query);
                    $tokens = $this->stringToArray($query);
                    $this->validationBrackets($tokens);
                    $tokens = $this->normalizeArray($tokens);
                    list($output, $index) = $this->arrayToQuery($tokens);
                    $this->set([
                        '_serialize' => ['json'],
                        'json' => json_encode($output),
                    ]);
                } else {
                    throw new Exception('Search query can not be empty');
                }

            } catch (Exception $ex) {
                $this->set([
                    '_serialize' => ['json'],
                    'json' => json_encode($ex->getMessage()),
                ]);
            }
        } else {
            try {
                $query = 'width < 200 or (height > 100 and height < 300)';
                if (!empty($query)) {
                    $query = strtolower($query);
                    $tokens = $this->stringToArray($query);
                    $this->validationBrackets($tokens);
                    $tokens = $this->normalizeArray($tokens);
                    list($output, $index) = $this->arrayToQuery($tokens);
                    echo $query;
                    echo '<pre>';
                    print_r($output);
                    echo '</pre>';
                    die();
                } else {
                    throw new Exception('Search query can not be empty');
                }

            } catch (Exception $ex) {
                echo $ex->getMessage();
                die();
            }
        }


    }

    /**
     * stringToArray method
     *
     * @param string $query
     * @return array Return array of tokens.
     *
     */
    private function stringToArray($query)
    {
        $hashSet = array_flip($this->delimiter);
        $tokens = array();
        $token = "";
        $splitLen = max(array_map('strlen', $this->delimiter)); //hardcoded - len delimiter
        $len = strlen($query);
        $pos = 0;

        while ($pos < $len) {
            for ($i = $splitLen; $i > 0; $i--) {
                $substr = substr($query, $pos, $i);
                if (isset($hashSet[$substr])) {

                    if ($token !== "") {
                        $tokens[] = $token;
                    }

                    $tokens[] = $substr;
                    $pos += $i;
                    $token = "";

                    continue 2;
                }
            }

            $token .= $query[$pos];
            $pos++;
        }

        if ($token !== "") {
            $tokens[] = $token;
        }

        $tokens = array_values(array_filter(array_map('trim', $tokens))); //remove whitespace from array
        return $tokens;
    }

    /**
     * validationBrackets method
     *
     * @param array $tokens
     * @throws Exception
     */
    private function validationBrackets($tokens)
    {
        //validate bracket position

        $bracketLeft = 0;
        $bracketRight = 0;
        for ($i = 0; $i < count($tokens); $i++) {
            if ($tokens[$i] == '(') {
                if (isset($tokens[$i - 1])) {
                    if (!in_array($tokens[$i - 1], $this->delimiter)) {
                        throw new Exception('Error in statement: Unknown "(" near ' . $tokens[$i - 1]);
                    }
                }

                if (isset($tokens[$i + 1])) {
                    if (in_array($tokens[$i + 1], $this->delimiter) && $tokens[$i + 1] != '(') {
                        throw new Exception('Error in statement: Unknown "(" near ' . $tokens[$i - 1]);
                    }
                }
                $bracketLeft++;
            }

            if ($tokens[$i] == ')') {
                if (isset($tokens[$i - 1])) {
                    if (in_array($tokens[$i - 1], $this->delimiter) && $tokens[$i - 1] != ')') {
                        throw new Exception('Error in statement: Unknown ")" near ' . $tokens[$i + 1]);
                    }
                }
                if (isset($tokens[$i + 1])) {
                    if (!in_array($tokens[$i + 1], $this->delimiter)) {
                        throw new Exception('Error in statement: Unknown ")" near ' . $tokens[$i + 1]);
                    }
                }
                $bracketRight++;
            }
        }

        //validate bracket count
        if ($bracketLeft != 0 || $bracketRight != 0) {
            if ($bracketLeft > $bracketRight) {
                throw new Exception('Error in statement: ")" is missing');
            }

            if ($bracketLeft < $bracketRight) {
                throw new Exception('Error in statement: "(" is missing');
            }

        }

        $this->bracketCount = $bracketLeft;
    }


    /**
     * @param array $tokens
     * @return mixed
     * @throws Exception
     */
    private function normalizeArray($tokens)
    {
        $matchingArray = array('>' => '<', '<' => '>', 'contains' => 'LIKE'); //for inverted token (100 > width)
        $operatorsField = array('>', '<', '=', 'contains'); //possible operators

        $allowedOperators = array(
            'filename' => array(ord('='), 'contains'),
            'width' => array(ord('<'), ord('=')),
            'height' => array(ord('>'), ord('<'), ord('='))
        );

        for ($i = 0; $i < count($tokens); $i++) {
            if (in_array($tokens[$i], array('and', 'or'))) {
                //Check for join operators in the beginning and end of query
                if (!isset($tokens[$i - 1])) {
                    throw new Exception('Error in statement: Unexpected ' . strtoupper($tokens[$i]) . ' on the left of  "' . $tokens[$i + 1] . '"');
                }
                if (!isset($tokens[$i + 1])) {
                    throw new Exception('Error in statement: Unexpected ' . strtoupper($tokens[$i]) . ' on the right of "' . $tokens[$i - 1] . '"');
                }

                continue;
            }

            if ($tokens[$i] == '(' || $tokens[$i] == ')') {
                continue;
            }

            //validation for operator
            $operator = '';
            $count = 0;
            foreach ($operatorsField as $item) {
                if (strpos($tokens[$i], $item)) {
                    $pos = strpos($tokens[$i], $item);
                    //check for many operators and check for operators validity
                    if (
                        ($tokens[$i][$pos - 1] == ' ' || (ctype_alpha($tokens[$i][$pos - 1]) || ctype_digit($tokens[$i][$pos - 1]))) &&
                        ($tokens[$i][$pos + strlen($item)] == ' ' || (ctype_alpha($tokens[$i][$pos + strlen($item)]) || ctype_digit($tokens[$i][$pos + strlen($item)])))
                    ) {
                        $count++;
                        $operator = $item;
                    }

                }
            }

            if ($operator != '') {
                if ($count > 1) {
                    throw new Exception('Error in statement: AND/OR condition missing');
                }

                $arrg = explode($operator, $tokens[$i]);
                if ($operator == 'contains' && !$this->Images->hasField(trim($arrg[0]))) {
                    throw new Exception('Error in statement: Unknown field for operator ' . $operator);
                }

                if (!$this->Images->hasField(trim($arrg[0])) && !$this->Images->hasField(trim($arrg[1]))) {
                    throw new Exception('Error in statement: Unknown field ' . $arrg[0]);
                }

                //swap fieldname and value if inverted
                if ($this->Images->hasField(trim($arrg[0]))) {
                    $field = trim($arrg[0]);
                    $value = trim($arrg[1]);
                } else {
                    $field = trim($arrg[1]);
                    $value = trim($arrg[0]);
                    if (array_key_exists($operator, $matchingArray)) {
                        $operator = $matchingArray[$operator];
                    }
                }

                if (array_key_exists($field, $allowedOperators)) {
                    if ($field == 'filename') {
                        if (!in_array(ord($operator), $allowedOperators[$field]) && !in_array($operator,
                                $allowedOperators[$field])
                        ) {
                            throw new Exception('Error in statement: Wrong operator ' . $operator . ' for field ' . $field);
                        }
                        if (array_key_exists($operator, $matchingArray)) {
                            $operator = $matchingArray[$operator];
                        }
                    } else {
                        if (!in_array(ord($operator), $allowedOperators[$field])) {
                            throw new Exception('Error in statement: Wrong operator ' . $operator . ' for field ' . $field);
                        }
                    }

                }

                $tokens[$i] = array(
                    'field' => $field,
                    'operator' => $operator,
                    'value' => $operator == 'LIKE' ? '%' . $value . '%' : $value
                );
            } else {
                throw new Exception('Error in statement: Unknown operator in ' . $tokens[$i]);
            }
        }
        return $tokens;
    }


    /**
     * arrayToQuery method
     *
     * @param $tokens
     * @param int $start_index
     * @return array
     */
    private function arrayToQuery($tokens, $start_index = 0)
    {
        $out = array();
        $token = '';
        $row = '';

        for ($i = $start_index; $i < count($tokens); $i++) {
            if ($this->bracketCount < 0) {
                return array($out, $i);
            }

            if ($tokens[$i] == 'or') {
                if ($token != '') {
                    $row = 'OR';
                    $out['OR'][$token['field'] . ' ' . $token['operator']] = $token['value'];
                }
            }

            if ($tokens[$i] == 'and') {
                if ($token != '') {
                    $out[$token['field'] . ' ' . $token['operator']] = $token['value'];
                }
            }

            if (is_array($tokens[$i])) {
                $token = $tokens[$i];
            }

            if ($tokens[$i] == '(') {
                $this->bracketCount--;
                if ($row == '') {
                    list($recurs_array, $newIndex) = $this->arrayToQuery($tokens, $i + 1);
                    $out[] = $recurs_array;
                    $i += $newIndex + 1;
                } else {
                    list($recurs_array, $newIndex) = $this->arrayToQuery($tokens, $i + 1);
                    $out[$row][] = $recurs_array;
                    $i += $newIndex + 1;

                }

            }

            if ($i >= count($tokens)) {
                return array($out, $i);
            }

            if ($tokens[$i] == ')') {
                if ($token != '') {
                    if ($row == '') {
                        $out[$token['field'] . ' ' . $token['operator']] = $token['value'];

                    } else {
                        $out[$row][$token['field'] . ' ' . $token['operator']] = $token['value'];
                    }
                }
                return array($out, $i);
            }
        }
        if ($token != '') {
            if ($row == '') {
                $out[$token['field'] . ' ' . $token['operator']] = $token['value'];
            } else {
                $out[$row][$token['field'] . ' ' . $token['operator']] = $token['value'];
            }
        }
        return array($out, $i);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $image = $this->Images->newEntity();
        if ($this->request->is('post')) {
            $upload_image = $this->request->getData()['upload'];
            if (!empty($upload_image)) {
                if (!empty($upload_image['name'])) {
                    list($width, $height) = getimagesize($upload_image['tmp_name']);
                    if ($this->request->getData()['height'] + $this->request->getData()['top'] <= $height &&
                        $this->request->getData()['width'] + $this->request->getData()['left'] <= $width
                    ) {
                        //create new name for image
                        $ext = substr(strtolower(strrchr($upload_image['name'], '.')), 1); //get the extension
                        $newFilename = time() . "_" . rand(000000, 999999) . '.' . $ext;

                        $basePath = WWW_ROOT . '/upload/images/'; //base path
                        if (!is_dir($basePath)) {
                            mkdir($basePath, 0777, true);;
                        }
                        $basePath .= $newFilename;
                        $imageCrop = new ImageResize($upload_image['tmp_name']);
                        $imageCrop->freecrop(
                            $this->request->getData()['width'],
                            $this->request->getData()['height'],
                            $x = $this->request->getData()['left'],
                            $y = $this->request->getData()['top']
                        );
                        $imageCrop->save($basePath);


                        $image = $this->Images->patchEntity($image, $this->request->getData());
                        $image->filename = $newFilename;
                        if ($this->Images->save($image)) {
                            $this->Flash->success(__('The image has been saved.'));

                            return $this->redirect(['action' => 'index']);
                        }
                        $this->Flash->error(__('The image could not be saved. Please, try again.'));
                    } else {
                        $this->Flash->error(__('Bad data for crop.'));
                    }
                }
            }
        }
        $this->set(compact('image'));
        $this->set('_serialize', ['image']);
    }
}
