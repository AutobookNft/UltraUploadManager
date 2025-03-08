<?php
namespace App\Util;


class FormatErrors


{

    public $errors;

    private $err = [];

    public function __construct($errors){

        $this->errors = $errors;

    }

        public function formatErrors()
        {

        $errors = json_decode($this->errors, true);

        $error0 = $this->errors->get('photos')[0];
        $error1 = $this->errors->get('photos')[1];

        foreach ($errors as $field => $messages) {
            $errors[$field] = implode(', ', $messages);
        }


            return $errors;
        }

    }
