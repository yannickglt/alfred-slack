alfred-slack
============

Open conversation with a contact in Slack

# To start
1. Download and install [Slack.alfredworkflow](https://github.com/packal/repository/raw/master/com.yannickglt.alfred2.slack/slack.alfredworkflow)
2. Create a custom app for your team following [these steps](#create-an-app-for-your-team).
3. Once you have your client ID and Secret, visit the address [http://yannickglt.github.io/alfred-slack/](http://yannickglt.github.io/alfred-slack/) to generate a unique code for authentication in the Workflow:
3. Launch the slack workflow with the parameter `--add-client` followed by the concatenation of the generated unique code and client Secret separated by semi-colons (e.g.: `CLIENT_SECRET:UNIQUE_CODE`).
You can add several clients if you want to collaborate with several teams. You just need to repeat the two last steps.
  
    Example: 
    ```
    slack --add-client 1234567890.123456789012|1234567890.123456789012.abcdef1234:1234567890abcdef1234567890abcdef
    ```
4. Launch the cache refresh by taping the command `--refresh`.

    Example:
    ```
    slack --refresh
    ```
    **The cache refresh may take up to several minutes depending on your organization size.**
  
5. Enjoy!

   Note: install the [Packal Updater](http://www.packal.org/workflow/packal-updater) workflow if you want automatic updates.

# How to use
- List channels or groups to open in the Slack app:

  ```
  slack <channel/group>
  ```
  ![image](https://cloud.githubusercontent.com/assets/1006426/10527597/a4c81c44-7391-11e5-9009-625d1e6957f1.png)


- List users to open in the Slack app:

  ```
  slack <user>
  ```
  ![image](https://cloud.githubusercontent.com/assets/1006426/10527601/aa77ab3c-7391-11e5-9e04-1b937ef35206.png)

- Open a channel, group or user in the Slack app:

  ```
  slack <channel/group/user>
  ```
  ![image](http://www.packal.org/sites/default/files/public/workflow-files/comyannickgltalfred2slack/screenshots/alfred-slack2.gif)

- List messages from a specific channel, group or user:

  ```
  slack <channel/group/user>
  ```
  ![image](https://cloud.githubusercontent.com/assets/1006426/10527030/918dd7f2-738e-11e5-9ea1-4bf74a0dd9cb.png)

- Send a message to a channel, group or user:

  ```
  slack <channel/group/user> <message>
  ```
  ![image](https://cloud.githubusercontent.com/assets/1006426/10527561/6966d26c-7391-11e5-8907-ee2999e3ef36.png)

- Mark all channels as read

  ```
  slack --mark
  ```

- List the files within the team

  ```
  slack --files <search>
  ```

- List the items starred

  ```
  slack --stars <search>
  ```

- Search both messages and files
  
  ```
  slack --search <query>
  ```

- Set the user presence (either active or away)

  ```
  slack --presence <active|away>
  ```

### Create an app for your team
1. Go to the URL [https://api.slack.com/apps/new](https://api.slack.com/apps/new) and click on the button `Create a Slack app`.

    ![image](https://cloud.githubusercontent.com/assets/1006426/25681953/9067e094-3056-11e7-9f8d-4ea2b0627eff.png)

2. Give it an app name e.g.: "Alfred Workflow", select your team in the list and click on the button `Create App`.

    ![image](https://cloud.githubusercontent.com/assets/1006426/25682019/d5db3450-3056-11e7-8f24-470463ce6dd5.png)

3. Note the client ID and Secret!

    ![image](https://cloud.githubusercontent.com/assets/1006426/25771653/e8aba62a-3257-11e7-88e0-050723b7058e.png)

> :warning: Never share the client secret on the web or on public repository