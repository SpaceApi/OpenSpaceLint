var apienv;

$(document).ready(function () {

    var specs8 = {
        "properties": {
            "api": {
                "type": "string",
                "required": true,
                "enum": ["0.8", "0.9", "0.11", "0.12"]
            },
            "space": {
                "type": "string",
                "required": true
            },
            "logo": {
                "type": "string",
                "required": true
            },
            "url": {
                "required": true,
                "type": "string"
            },
            "address": {
                "required": false,
                "type": "string"
            },
            "phone": {
                "required": false,
                "type": "string"
            },
            "lat": {
                "required": false,
                "type": "number"
            },
            "lon": {
                "required": false,
                "type": "number"
            },
            "cam": {
                "required": false,
                "type": "array",
                "items": {
                    "type": "string"
                },
                "minItems": 1
            },
            "stream": {
                "required": false,
                "type": "object"
            },
            "open": {
                "required": true,
                "type": "boolean"
            },
            "status": {
                "required": false,
                "type": "string"
            },
            "lastchange": {
                "required": false,
                "type": "number"
            },
            "events": {
                "required": false,
                "type": "array",
                "items": {
                    "required": false,
                    "type": "object",
                    "properties": {
                        "name": {
                            "required": true,
                            "type": "string"
                        },
                        "type": {
                            "required": true,
                            "type": "string"
                        },
                        "t": {
                            "required": true,
                            "type": "number"
                        },
                        "extra": {
                            "required": false,
                            "type": "string"
                        }
                    }
                }
            }
        }
    };

    var specs9 = JSV.inherits(specs8, {
        "properties": {
            "contact": {
                "required": false,
                "type": "object",
                "properties": {
                    "phone": {
                        "required": false,
                        "type": "string"
                    },
                    "sip": {
                        "required": false,
                        "type": "string"
                    },
                    "keymaster": {
                        "required": false,
                        "type": "array",
                        "items": {
                            "type": "string"
                        }
                    },
                    "irc": {
                        "required": false,
                        "type": "string"
                    },
                    "twitter": {
                        "required": false,
                        "type": "string"
                    },
                    "email": {
                        "required": false,
                        "type": "string"
                    },
                    "ml": {
                        "required": false,
                        "type": "string"
                    },
                    "jabber": {
                        "required": false,
                        "type": "string"
                    }
                }
            }
        }
    });
    // phone moved to the contact structure
    delete specs9.properties.phone;

    var specs11 = JSV.inherits(specs9, {
        "properties": {
            "icon": {
                "type": "object",
                "required": true,
                "properties": {
                    "open": {
                        "type": "string",
                        "required": true
                    },
                    "closed": {
                        "type": "string",
                        "required": true
                    }
                }
            }
        }
    });

    var specs12 = JSV.inherits(specs11, {
        "properties": {
            "sensors": {
                "required": false,
                "type": "array",
                "items": {
                    "type": "object",
                    "properties": {
                        "temp": {
                            "required": false,
                            "type": "object"
                        }
                    }
                }
            },
            "feeds": {
                "required": false,
                "type": "array",
                "items": {
                    "type": "object",
                    "properties": {
                        "name": {
                            "required": true,
                            "type": "string"
                        },
                        "type": {
                            "required": false,
                            "type": "string"
                        },
                        "url": {
                            "required": true,
                            "type": "string"
                        }
                    }
                }
            }
        }
    });

    apienv = JSV.createEnvironment("json-schema-draft-03");
    apienv.createSchema(specs8, undefined, "http://openspace.slopjong.de/specs8");
    apienv.createSchema(specs9, undefined, "http://openspace.slopjong.de/specs9");
    apienv.createSchema(specs11, undefined, "http://openspace.slopjong.de/specs11");
    apienv.createSchema(specs12, undefined, "http://openspace.slopjong.de/specs12");
});