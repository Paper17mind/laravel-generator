# Laravel and vue project builder

save your time to make laravel project,
with this project you can generate laravel project structure including :

- Controllers
- Database Migrations
- Models
- Routing
- Graphql mutation, types, query

for frontend, currently support only for vue js with following framework: 
- vue2 with ![Vuetify](https://vuetifyjs.com)
- vue3 with ![Quasar](https://quasar.dev)

some configuration required for connecting generated frontend to backend, you must define base url of your backend endpoint.

###### api to genete project

to generate project, for currently you can type 
http://localhost/[folder name of this file]?generate={id project}&type=project

after that you can find output object from 
![public](https://github.com/Paper17mind/laravel-generator/tree/main/public) with folder name of your peoject id
