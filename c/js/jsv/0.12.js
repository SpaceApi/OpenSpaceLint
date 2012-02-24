var os_schema = 
	{
		"properties":{
			
			"api":{
				"type":"string", 
				"required":true, 
				"enum":["0.12"]
			},
			"space":{
				"type":"string", 
				"required":true
			},
		       "icon":{
			       "type":"object",
			       "required":true,
			       "properties":{
				       "open":{
					       "type":"string", 
					       "required":true
					},
				       "closed":{
					       "type":"string",
					       "required":true
					}
				},
			},
			"url":{
				"required":true, 
				"type":"string"
			},
			"address":{
				"required":false, 
				"type":"string"
			},
			"contact":{
				"required":false, 
				"type":"object",
				"properties":{
					"phone":{
						"required":false,
						"type":"string"
					},
					"sip":{
						"required":false,
						"type":"string"
					},
					"keymaster":{
						"required":false,
						"type":"array",
						"items":{
							"type":"string"
						}
					},
					"irc":{
						"required":false,
						"type":"string"
					},
					"twitter":{
						"required":false,
						"type":"string"
					},
					"email":{
						"required":false,
						"type":"string"
					},
					"ml":{
						"required":false,
						"type":"string"
					},
					"jabber":{
						"required":false,
						"type":"string"
					}
				}
			},
			"lat":{
				"required":false,
				"type":"number"
			},
			"lon":{
				"required":false,
				"type":"number"
			},
			"cam":{
				"required":false,
				"type":"array",
				"items":{
					"type":"string"
				}
			},
			"stream":{
				"required":false,
				"type":"array",
				"items":{
					"type":"string"
				}
			},
			"open":{
				"required":true,
				"type":"boolean"
			},
			"status":{
				"required":false,
				"type":"string"
			},
			"lastchange":{
				"required":false,
				"type":"number"
			},
			"events":{
				"required":false,
				"type":"object",
				"properties": {
					"name":{
						"required": true,
						"type":"string",
					},
					"type":{
						"required": true,
						"type":"string",
					},
					"t":{
						"required": true,
						"type":"number",
					},
					"extra":{
						"required": false,
						"type":"string",
					}
				}
			},
			"sensors":{
				"required": false,
				"type":"object",
				"properties":{
					"temp":{ 
						"required": true,
						"type":"array",
						"items":{
							"required": true,
							"type":"array",
							"items":{
								"required": true,
								"minItems": 2,
								"maxItems": 2,
								"type": "string"
							}
						}
					}
				}
			},
			"feeds":{
				"required": false,
				"type": "object",
				"properties": {
					"name":{
						"required": true,
						"type": "string"
					},
					"type":{
						"required": false,
						"type": "string"
					},
					"url":{
						"required": true,
						"type": "string"
					}
				}
			}
		}
	}