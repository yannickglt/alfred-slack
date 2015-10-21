alfred-slack
============

Open conversation with a contact in Slack

# To start
1. Generate a token at this address: `https://api.slack.com/`
2. Launch the slack workflow with the parameter `--token` followed by the token.

  Example: 
  ```
  slack --token xoxp-1234567890-1234567890-1234567890-ab1234
  ```
3. Launch the cache refresh by taping the command `--refresh`.

  Example:
  ```
  slack --refresh
  ```
4. Enjoy!

# To use
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
