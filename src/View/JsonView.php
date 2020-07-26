<?php

namespace Rest\View;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\View\View;

/**
 * Json View
 *
 * Default view class for rendering API response
 */
class JsonView extends View
{

    /**
     * List of special view vars.
     *
     * @var array
     */
    protected $_specialVars = ['_serialize', '_jsonOptions', '_jsonp'];

    /**
     * Renders api response
     *
     * @param string|null $view Name of view file to use
     * @param string|null $layout Layout to use.
     * @return string|null Rendered content or null if content already rendered and returned earlier.
     * @throws Exception If there is an error in the view.
     */
    public function render($view = null, $layout = null)
    {
        if ($this->hasRendered) {
            return null;
        }

        $this->response = $this->response->withType('json');

        $this->layout = "Rest.rest";

        $content = [
            'status' => 'OK',
        ];

        $code = $this->response->getStatusCode();

        if ($code != 200) {
            $content['status'] = "NOK";
        }

        if (!isset($this->viewVars['_serialize'])) {
            foreach ($this->viewVars as $name => $values) {
                if ($name != 'status') {
                    $this->viewVars['_serialize'][] = $name;
                }
            }

            if (isset($this->viewVars['_serialize'])) {
                if (count($this->viewVars['_serialize']) === 1) {
                    $this->viewVars['_serialize'] = $this->viewVars['_serialize'][0];
                }
            } else {
                $content['status'] = 'NOK';
                $this->viewVars['message'] = ['message' => 'empty response'];
                $this->viewVars['_serialize'] = 'message';
            }
        }

        $content['result'] = $this->renderResult($this->viewVars);

        $this->Blocks->set('content', $this->renderLayout(json_encode($content), $this->layout));

        $this->hasRendered = true;

        return $this->Blocks->get('content');
    }

    /**
     * Cumstom Render for api response
     *
     * @param string|null $view Name of view file to use
     * @param string|null $layout Layout to use.
     * @return string|null Rendered content or null if content already rendered and returned earlier.
     * @throws \Cake\Core\Exception\Exception If there is an error in the view.
     */
    public function renderResult($view = null, $layout = null)
    {
        $serialize = false;
        if (isset($this->viewVars['_serialize'])) {
            $serialize = $this->viewVars['_serialize'];
        }

        if ($serialize !== false) {
            $result = $this->_serialize($serialize);
            if ($result === false) {
                throw new RuntimeException('Serialization of View data failed.');
            }

            return (string)$result;
        }
        if ($view !== false && $this->_getViewFileName($view)) {
            return parent::render($view, false);
        }
    }

    /**
     * Serialize view vars
     *
     * ### Special parameters
     * `_jsonOptions` You can set custom options for json_encode() this way,
     *   e.g. `JSON_HEX_TAG | JSON_HEX_APOS`.
     *
     * @param array|string|bool $serialize The name(s) of the view variable(s)
     *   that need(s) to be serialized. If true all available view variables.
     * @return string|false The serialized data, or boolean false if not serializable.
     */
    protected function _serialize($serialize)
    {
        $data = $this->_dataToSerialize($serialize);

        $jsonOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT |
            JSON_PARTIAL_OUTPUT_ON_ERROR;

        if (isset($this->viewVars['_jsonOptions'])) {
            if ($this->viewVars['_jsonOptions'] === false) {
                $jsonOptions = 0;
            } else {
                $jsonOptions = $this->viewVars['_jsonOptions'];
            }
        }

        if (Configure::read('debug')) {
            $jsonOptions |= JSON_PRETTY_PRINT;
        }

        return json_encode($data, $jsonOptions);
    }

    /**
     * Returns data to be serialized.
     *
     * @param array|string|bool $serialize The name(s) of the view variable(s) that
     *   need(s) to be serialized. If true all available view variables will be used.
     * @return mixed The data to serialize.
     */
    protected function _dataToSerialize($serialize = true)
    {
        if ($serialize === true) {
            $data = array_diff_key(
                $this->viewVars,
                array_flip($this->_specialVars)
            );

            if (empty($data)) {
                return null;
            }

            return $data;
        }

        if (is_array($serialize)) {
            $data = [];
            foreach ($serialize as $alias => $key) {
                if (is_numeric($alias)) {
                    $alias = $key;
                }
                if (array_key_exists($key, $this->viewVars)) {
                    $data[$alias] = $this->viewVars[$key];
                }
            }

            return !empty($data) ? $data : null;
        }

        return $this->viewVars[$serialize] ?? null;
    }
}
