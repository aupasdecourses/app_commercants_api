# Using TOKEN directly in APP for resseting password

* Reset config.yml
```yml
            - { path: '^/', priorities: ['json', 'html'], fallback_format: 'json', prefer_extension: false }
```
To
```yml
            - { path: '^/', priorities: ['json'], fallback_format: 'json', prefer_extension: false }
```
* Remove Resources/FOSUserBundle
* Remove Resources/views/resettingOk.html.twig