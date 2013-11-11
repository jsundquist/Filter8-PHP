<?php

// Filter8 Client SDK
//
// Copyright (c) 2013 Filter8 ( http://www.filter8.com )
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

class Filter8
{
	public static $tags = array('Account' => 'Words and phrases used when attempting to share account information, such as password and username.',
								'Alcohol-Drug' => 'Words and phrases with alcohol and drug connotations.',
								'AsciiArt' => 'Combinations of symbols that are used to convey sexual and other inappropriate images.',
								'Bigotry-Racism' => 'Words and phrases with racist, religious, and other bigotry type connotations.',
								'Bullying' => 'Words and phrases used when a user attempts to bully another user.',
								'Grooming' => 'Words and phrases used by sexual predators to groom another user.',
								'Harm-Abuse' => 'Words and phrases used when a user communicates about harming themselves or another.',
								'PII' => 'Words and phrases used when attempting to share personally identifiable information (PII).',
								'Sexual' => 'Words and phrases with sexual connotations.',
								'Spam' => 'Words and phrases used by spammers.',
								'Threats' => 'Words and phrases used when a user attempts to threaten another user.',
								'Vulgarity' => 'Words and phrases that are profane or inappropriate slang.',
								'Weapons' => 'Words and phrases used to communicate about weapons.');

	public static $locales = array('en' => 'English',
								   'nl' => 'Dutch',
								   'fr' => 'French',
								   'de' => 'German',
								   'it' => 'Italian',
								   'pl' => 'Polish',
								   'pt' => 'Portuguese',
								   'ru' => 'Russian',
								   'es' => 'Spanish');

	
	// The base URL can be modified to work with debug endpoints (ex: http://www.runscope.com => https://api-filter8-com-BUCKETKEY.runscope.net )
	public $baseURL = 'https://api.filter8.com/content/item.js';
	
	// API Key and Secret are found on your Filter8 Account Dashboard
	public $apiKey;
	public $apiSecret;
	
	// Enable / Disable Specific Filters. Blacklist Filter is assumed if no filter is explicitly enabled.
	// TRUE, "1", anything that is !empty() will enable the filter.
	public $blacklistFilterEnabled;		
	public $charactersFilterEnabled;
	public $emailsFilterEnabled;
	public $phoneNumbersFilterEnabled;
	public $urlsFilterEnabled;
	public $wordsFilterEnabled;
	
	// Specify the Filter Operation: 'locate' or 'replace' (replace is the default).
	public $operation;
	// If using Replace operation, customize the replacement character (* is the default) OR a complete string for each match.
	public $replacement;
	
	// Not specifying an array of locales will match against ALL locales
	// Example: ['en','es']
	public $blacklistLocales;	
	// Not specifying an array of tags will match against ALL tags
	// Example: ['Vulgarity','Grooming']
	public $blacklistTags;
	// 'None', 'Mild', 'Medium', 'High', 'Severe' - default is to match against ALL severities
	public $blacklistSeverity;
	
	// Specify the characters to search for if the Characters Filter is enabled. Example: '*#$%'
	public $charactersFilterChars;
	
	// Maximum Length when trying to match an email, defaults to 50.
	public $emailsMaxMatchLength;
	// "Quality Value" penalty if user tries to use spaces, defaults to -0.05
	public $emailsSpacePenalty;
	
	// Maximum Length when trying to match a phone number, defaults to 20.
	public $phoneNumbersMaxMatchLength;
	// Minimum Length when trying to match a phone number, defaults to 6.
	public $phoneNumbersMinMatchLength;
	// "Quality Value" penalty if user tries to use random separators, defaults to -0.05
	public $phoneNumbersSeparatorPenalty;
	// "Quality Value" penalty if user tries to use spaces, defaults to -0.05
	public $phoneNumbersSpacePenalty;
	// "Quality Value" penalty if user tries to words, defaults to -0.03
	public $phoneNumbersWordPenalty;
	
	// Maximum Length when trying to match a url, defaults to 50.
	public $urlsMaxMatchLength;
	// "Quality Value" penalty if user tries use spaces, defaults to -0.05
	public $urlsSpacePenalty;
	
	// Specify the words to search for if the Word Filter is enabled. Example: ['quirx','custard']
	public $wordsFilterWords;
	
	
	// Constructs and performs a filter query based on the configuration of the class properties
	function query($content) {
		$timestamp = time();
		$nonceParam = 'nonce=' . $timestamp;
		$url = $this->baseURL . '?' . $nonceParam;
		$credentials = $this->apiKey . ':' . md5($timestamp . $this->apiSecret);
		$data = 'content=' . urlencode($content);
		
		if (!empty($this->operation)) {
			$data = $data.'&'.'operation='.$this->operation;
		}
		if (!empty($this->replacement) && strcmp($this->replacement, '*') != 0) {
			if (strlen($this->replacement) == 1) {
				$data = $data.'&'.'replaceChar='.$this->replacement;
			} else {
				$data = $data.'&'.'replaceString='.$this->replacement;
			}
		}
		if (!empty($this->blacklistFilterEnabled)) {
			$data = $data.'&'.'blacklist.enabled=true';
			if (!empty($this->blacklistSeverity)) {
				$data = $data.'&'.'blacklist.severity='.strtolower($this->blacklistSeverity);
			}
			if (!empty($this->blacklistTags) && count(Filter8::$tags) != count($this->blacklistTags)) { 
				foreach ($this->blacklistTags as $tag) {
					$data = $data.'&'.'blacklist.tag='.$tag;
				}
			}
			if (!empty($this->blacklistLocales) && count(Filter8::$locales) != count($this->blacklistLocales)) {
				foreach ($this->blacklistLocales as $locale) {
					$data = $data.'&'.'blacklist.locale='.$locale;
				}
			}
		}

		if (!empty($this->charactersFilterEnabled) && !empty($this->charactersFilterChars)) {
			$data = $data.'&'.'characters.enabled=true';
			foreach (str_split($this->charactersFilterChars) as $char) {
				$data = $data.'&'.'characters.character='.$char;
			}
		}
		
		if (!empty($this->emailsFilterEnabled)) {
			$data = $data.'&'.'emails.enabled=true';
			if (!empty($this->emailsMaxMatchLength)) {
				$data = $data.'&'.'emails.maximumMatchLength='.$this->emailsMaxMatchLength;
			}
			if (!empty($this->emailsSpacePenalty)) {
				$data = $data.'&'.'emails.spacePenalty='.$this->emailsSpacePenalty;
			}
		}
		
		if (!empty($this->phoneNumbersFilterEnabled)) {
			$data = $data.'&'.'phoneNumbers.enabled=true';	
			if (!empty($this->phoneNumbersMaxMatchLength)) {
				$data = $data.'&'.'phoneNumbers.maximumMatchLength='.$this->phoneNumbersMaxMatchLength;
			}
			if (!empty($this->phoneNumbersMinMatchLength)) {
				$data = $data.'&'.'phoneNumbers.minimumMatchLength='.$this->phoneNumbersMinMatchLength;
			}
			if (!empty($this->phoneNumbersSeparatorPenalty)) {
				$data = $data.'&'.'phoneNumbers.separatorPenalty='.$this->phoneNumbersSeparatorPenalty;
			}
			if (!empty($this->phoneNumbersSpacePenalty)) {
				$data = $data.'&'.'phoneNumbers.spacePenalty='.$this->phoneNumbersSpacePenalty;
			}
			if (!empty($this->phoneNumbersWordPenalty)) {
				$data = $data.'&'.'phoneNumbers.wordPenalty='.$this->phoneNumbersWordPenalty;
			}
		}
		
		if (!empty($this->urlsFilterEnabled)) {
			$data = $data.'&'.'urls.enabled=true';
			if (!empty($this->urlsMaxMatchLength)) {
				$data = $data.'&'.'urls.maximumMatchLength='.$this->urlsMaxMatchLength;
			}
			if (!empty($this->urlsSpacePenalty)) {
				$data = $data.'&'.'urls.spacePenalty='.$this->urlsSpacePenalty;
			}
		}
		
		if (!empty($this->wordsFilterEnabled) && !empty($this->wordsFilterWords)) {
			$data = $data.'&'.'words.enabled=true';
			foreach ($this->wordsFilterWords as $word) {
				$data = $data.'&'.'words.word='.$word;
			}
		}
		
		// construct the url request
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $credentials);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($ch);
		curl_close($ch);
		$response_json = json_decode($response);
		// return the json object
		return $response_json;
	}
	
}

?>
