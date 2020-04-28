# Docker Development Environment


## Getting Started
Docker will spin up a container which is accessible locally at [http://localhost](http://localhost)

```bash
cd docker
./dev up
```

## Database 

You will do this the very first time you spin up the docker container.  

```bash
# Run Migrations
cd docker
./dev migrate migrate
# Seed the Database
./dev reset
```

### Migrations

Docker will attempt to run migrations every time the environment is brought up.  
If you want to view or run migrations manually, the following commands are available from within the docker folder. 

To view migration status: `./dev migrate status`

To run migrations: `./dev migrate migrate`

To create a migration: 
```bash
./dev exec bash
./vendor/bin/phinx create MigrationNameGoesHere
```

### Tests

Marketplace Admin has two test suites.  Unit & Integration. Before running the integration tests
it is *NECESSARY* to reset the database.  A script exists to do just that. 

```bash
./dev test
```

Or, you can manually run the integration tests. 

```bash
docker exec -it docker_admin_1 /bin/bash
cd /app
./vendor/bin/phinx seed:run
./vendor/bin/phpunit --testsuite integration
```

To run ONLY the unit tests:

```bash
docker exec -it docker_admin_1 /bin/bash
cd /app
./vendor/bin/phpunit --testsuite unit
```

#### Problems with Integration Tests

* Events are fired to post data to RA which will permanently fail due to duplicate UNIQUE_ID's


### Environment Variables

Below, you will find environment variables that can be set for many configuration items in the Marketplace Admin.  In the examples below we set the environment variables at run time however all of the environment variables may also be exported to avoid running them on the command line when bringing up the docker container.

Exporting environment variables:
```bash
export ADMIN_MYSQL_PORT=3307
./dev up
```

Works the same as setting them at run time:

```ADMIN_MYSQL_PORT=3307 ./dev up```


#### XDEBUG

First, identify your local hosts IP address.  Once identified you can set the IP address with the following.

`XDEBUG_HOST=YOUR.IP.GOES.HERE ./dev up`

#### Angular Marketplace Admin 

Without changing any configuration, the marketplace admin angular gui is available at 
[http://localhost:81](http://localhost:81) The port can be changed when bringing up the docker
environment by using the enviornment variable NG_ADMIN_PORT

`NG_ADMIN_PORT=8001 ./dev up`

#### Coordinating with RA

In order to run both Marketplace Admin and ADR-RA at the same time in development we need to modify the ports that are tied to our host machine. 

The following will set your MySQL port to 3307 and AMQP manager to port 15673.
After spinning up the Marketplace admin you will be able to spin up RA. 
`ADMIN_MYSQL_PORT=3307 ADMIN_AMQP_PORT=15673 ./dev up`

After spinning up docker, in order to have your local dev instance of Marketplace admin communicate with RA you will need to identify the container's internal IP address. 

```bash
docker exec -it docker_admin_1 /bin/bash
cat /etc/hosts
```

If your containers IP address is `172.25.0.4` then you will use `172.25.0.1` when building RA's endpoint.

Set it as follows: 
`RA_ENDPOINT="http://172.25.0.1:8081/api/" ./dev up`