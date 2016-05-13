# Setting Up New WordPress Website with Automatic Deploys

CodeCola has [a base repository skeleton](https://github.com/CodeColaLLC/WordPress) to fork in order to create a WordPress website. It includes a "starter" theme called [_s](http://underscores.me/) and the proper .gitignore file to exclude non-development WordPress core files. It also ships with a deploy script that can be configured to automatically update the theme and/or plugin files for a production website whenever someone pushes to the master branch.

## Creating a new repository

The first step is to "fork" the *[CodeColaLLC/WordPress](https://github.com/CodeColaLLC/WordPress)* repository for the particular client website you are working on. Forking within an organization cannot be done with the GitHub UI but it is easy enough to emulate by hand:

1. Visit [GitHub.com](https://github.com/) and create a new, empty repository in the *CodeColaLLC* organization by clicking the *+* in the header. Don't add a readme, .gitignore, or license file. Name it after your client, e.g. *acme-co-wordpress*.

1. On your local computer, clone the newly created repository, e.g.
  ```
  git clone https://github.com/CodeColaLLC/acme-co-wordpress
  ```

1. Navigate into the cloned repository's directory, e.g.
  ```
  cd acme-co-wordpress
  ```

1. Add the base WordPress repository as an upstream source:
  ```
  git remote add upstream https://github.com/CodeColaLLC/WordPress
  ```

1. Finally, merge in the upstream repository's code with
  ```
  git pull upstream master
  ```

Now the repository is linked to the original WordPress repository and can be used normally. If changes are made to the WordPress repository that you want to sync with the client's instance, simply use `git pull upstream master` to merge in the changes again.

## Installing WordPress locally

The newly created repository will have a basic WordPress theme in it, a deployment script, and pretty much nothing else. To install WordPress:

1. [Download it](https://wordpress.org/latest.zip) from [their website](https://wordpress.org/).

1. Extract the zip file.

1. Copy the contents from the *wordpress* directory inside the extracted zip file contents into your repository's root (e.g. *acme-co-wordpress*).

1. Assuming you have Apache, PHP, and MySQL installed and running locally and your repository is located within the *public_html* or equivalent public root location, you should be able to navigate to http://localhost/*path/to*/*acme-co-wordpress* to visit your local installation.

1. Using your local phpMyAdmin instance or a terminal, create a new database for this WordPress website, e.g. `acmeco_wp`.

1. Follow the installation wizard with WordPress to complete the setup.

Now you will have a local WordPress instance running to design with.

## Installing WordPress on the server

In order to install WordPress on the client's web server, you will need SSH access to the server. Accessing SSH will vary depending on the hosting provider, and may require enabling in cPanel or an equivalent.

1. SSH into the hosting web server for the client, e.g.
  ```
  ssh acmeco@acmeco.com
  ```

1. Once authenticated, make sure you are in the user's home directory.

1. Run an `ls` command, looking for the public root (such as *public_html*, *htdocs*, or *www*). Future examples will consider this directory to be called *public_html*. 

1. If **any** files exist in this public root directory, the next command will fail. Delete or move any existing files before running the next command.

1. Assuming Git is installed on the host, clone the client's WordPress website into the public root directory with
  ```
  git clone https://github.com/CodeColaLLC/acme-co-wordpress public_html
  ```

1. Download WordPress with
  ```
  wget https://wordpress.org/latest.zip
  ```

1. Extract the zip file with
  ```
  unzip latest.zip
  ```

1. Move the extracted *wordpress* directory into the public root, e.g.
  ```
  mv wordpress/* public_html
  ```

1. You should now be able to access the WordPress install wizard on the web server. Navigate to the website's domain (e.g. http://*acmeco.com*) to get to the installation wizard.

1. Using cPanel or equivalent, create a MySQL database for this WordPress instance as well as a user who has access to it. A common pattern is to create a database named something like `acmeco_wp` and a user with the same name, and then grant the user all privileges for the database. Always use a random, strong password for the user.

1. Complete the WordPress installation wizard using the MySQL credentials.

At this point, a running copy of WordPress will be installed on the server, and because we cloned the repository, the *_s* theme should be available. Try logging into the WordPress Dashboard and selecting the theme to verify that it exists.

## Setting up automatic deploy

The last step is to configure the GitHub repository to trigger an automatic deployment whenever someone pushes to the master branch.

1. Navigate to the repository in GitHub, e.g. *acme-co-wordpress*.

1. Click *Settings*, then *Webhooks & services*, then *Add webhook*.

1. In the *Payload URL* field, enter the path to the hosted WordPress installation, followed by *deploy.php*, e.g. `http://acmeco.com/deploy.php`

1. In the *Secret* field, choose a long, random, unpredictable token. You can generate one at a website like [this](http://randomkeygen.com/) (see the *Ft. Knox Passwords* section). Keep track of this "secret" for now, but don't store it anywhere permanently.

1. Click *Add webhook*.

1. SSH into the client's web server.

1. Modify the bash profile or equivalent with your favorite editor, e.g.
  ```
  vim ~/.bash_profile
  ```

1. Add a new line to create an environment variable called *GIT_TOKEN* and set it to your "secret," e.g.
  ```
  GIT_TOKEN=1234568790abcdefg
  ```

1. Save and close the profile file, then refresh your terminal session by running
  ```
  source ~/.bash_profile
  ```

Now we should be at a point where the deploy script will be executed every time anyone pushes to the master branch. To try it, make sure the production website's theme is set to a theme being tracked by your repository, then try making a change to the theme in the master branch and committing/pushing it. It should automatically be reflected on the web host.

## Creating/renaming themes and plugins

It is important to note that the *.gitignore* ignores all files in the repository by default and only whitelists certain files and directories. This is because there are so many WordPress core files, and we cannot rely on their name and number to be consistent as WordPress evolves.

If you rename the *_s* theme, create a new theme, or create a plugin for the website, you should open the *.gitignore* file and make sure to whitelist them.

For example, to un-ignore a new theme called *acmeco*, add `!wp-content/themes/acmeco/` after `wp-content/themes/*` in the *.gitignore*.

To un-ignore a new plugin called *acmeco*, add `!wp-content/plugins/acmeco/` after `wp-content/plugins/*` in the *.gitignore*.
