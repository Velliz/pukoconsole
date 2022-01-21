
-- Puko Framework Console --

setup    Installation and First time project setup
         [db]
         [secure]
         [auth] [name]
         [controller] [view] [name]
         [controller] [service] [name]
         [model] [add] [name] [schema]
         [model] [update] [name] [schema]
         [model] [remove] [name] [schema]

refresh  Reload and apply Database updates from external sources without change config file
         [db]
                  
routes   Routing generation and auto create associated controller files
         [list]
         [error]
         [lost]
         [service] [add] [url]
         [service] [update] [url]
         [service] [crud] [url]
         [view] [add] [url]
         [view] [update] [url]
         [console] [add] [url]
         [console] [update] [url]

generate Auto generate wizard for Database and User Interface
         [db]
         [ui]

language Scan and build all say() directive to master.json file
         <directory path>

serve    Start project on localhost with PHP build in server
         [port]

element  Generate or download view element
         <name> [add]
         <name> [download]

tests    Start unit testing (beta)

cli      Execute PHP code directly without web servers a.k.a console mode
         <router path>
         
help     Show help menu

version  Show console version

