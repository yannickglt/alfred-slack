alfred-slack
============

Open conversation with a contact in Slack

# To start
1. Download and install [Slack.alfredworkflow](https://github.com/packal/repository/raw/master/com.yannickglt.alfred2.slack/slack.alfredworkflow)
2. Generate a token at this address: [https://api.slack.com/web](https://api.slack.com/web)
3. Launch the slack workflow with the parameter `--add-token` followed by the token. You can add several token if you want to collaborate with several teams.

  Example: 
  ```
  slack --add-token xoxp-1234567890-1234567890-1234567890-ab1234
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
