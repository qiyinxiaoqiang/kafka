# mac安装kafka

### 1.安装最新版的kafka

```bash
brew install kafka
```
这将安装所有的依赖,包括zookeeper

### 2.启动zookeeper
```bash
brew services start zookeeper //启动zookeeper
zkServer start //或者这样启动
```
可以用 `brew info zookeeper` 命令查看zookeeper的相关信息，包括启动命令


### 3.启动kafka

```bash
brew services start kafka //启动kafka
kafka-server-start /usr/local/etc/kafka/server.properties //或者这样启动
```
同样可以用 `brew info kafka` 命令查看kafka的相关信息，包括启动命令

### 4. 创建一个topic

```bash
/usr/local/bin/kafka-topics --create --zookeeper localhost:2181 --replication-factor 1 --partitions 1 --topic test
```

创建了一个名字为`test`的`topic`, `topic`的名字最好是全e文，不要有 `_ .`等特殊符号，可以用以下命令查看创建的 `topic`

```bash
/usr/local/bin/kafka-topics --list --zookeeper localhost:2181 //查看topic
/usr/local/bin/kafka-topics --delete --zookeeper localhost:2181 --topic entere //删除名为entere的topic
```
### 5. 发送消息 producer

```bash
/usr/local/bin/kafka-console-producer --broker-list localhost:9092 --topic test

hello this is a test message
```

### 6. 消费消息 consumer

```bash
/usr/local/bin/kafka-console-consumer --zookeeper localhost:2181 --topic test --from-beginning
```

### 7. 配置多个broker集群
到目前为止，我们都是在单个broker上运行的，但是这没啥好玩的。对于Kafka来说，单个broker其实就是一个大小为1的集群，所以对于启动多个broker的实例来说，道理也是一样的，并没有太多变化。但是为了感觉一下他，就让我们将我们的集群扩充道3个节点（仍然全部运行在我们的本地机器上）。
首先我们为每一个broker建一个配置文件：

```bash
cp config/server.properties config/server-1.properties 
cp config/server.properties config/server-2.properties
```

现在，编辑这些新文件，并设置以下属性：

```bash
config/server-1.properties:
    broker.id=1
    port=9093
    log.dir=/tmp/kafka-logs-1

config/server-2.properties:
    broker.id=2
    port=9094
    log.dir=/tmp/kafka-logs-2
```

其中broker.id属性是一个不重复的常量，用来表示集群中每个节点的名字。我们在这里不得不重写port和log.dir，这只是因为我们是在同一台机器上运行这些命令，而我们要防止多个borker使用同一个端口注册而覆盖彼此的内容。

我们已经有了Zookeeper并且我们的单节点已经启动，所以我们现在需要启动这两个新节点：

```bash
/usr/local/bin/kafka-server-start config/server-1.properties &
/usr/local/bin/kafka-server-start config/server-2.properties &
```

现在创建一个有三个备份因子的新topic：

```bash
/usr/local/bin/kafka-topics --create --zookeeper localhost:2181 --replication-factor 3 --partitions 1 --topic my-replicated-topic
```

好了，现在我们有一个集群了，但是我们怎么知道每个个broker都在做什么呢？让我们运行`describe topics`命令来看看：

```bash
/usr/local/bin/kafka-topics --describe --zookeeper localhost:2181 --topic my-replicated-topic


Topic:my-replicated-topic    PartitionCount:1    ReplicationFactor:3 Configs:

Topic: my-replicated-topic  Partition: 0    Leader: 1   Replicas: 1,2,0 Isr: 1,2,0

```

这是上面输出的说明。第一行给出了所有分区的总结，此外每一行都是一个分区的信息。因为我们现在在这个topic上只有两个分区，所以就只有两行。

"leader" 负责给定分区中所有的读和写的任务。分区将随即选取一个节点作为leader。

“replicas” 列出了所有当前分区中的副本节点。不论这些节点是否是leader或者是否处于激活状态，都会被列出来。

“isr” 是表示“在同步中”的副本节点的列表。是replicas列表的一个子集，包含了当前处于激活状态的节点，并且leader节点开头。

注意在我们的例子中，节点1该topic仅有的一个分区中的leader节点。

我们可以在之前我们创建的topic中运行同样的命令，来看看是什么情况：

```bash
/usr/local/bin/kafka-topics --describe --zookeeper localhost:2181 --topic test


Topic:test    PartitionCount:1    ReplicationFactor:1 Configs:

Topic: test Partition: 0    Leader: 0   Replicas: 0 Isr: 0

```

看，和猜测的一样 -- 在之前的topic下没有副本节点，且其运行在server 0上，它是我们在创建topic时在集群中创建的唯一一个server。

让我们向我们的新topic发布一些消息：

```bash
/usr/local/bin/kafka-console-producer --broker-list localhost:9092 --topic my-replicated-topic

my test message 1
my test message 2

```

现在让我们消费这些消息：

```bash
/usr/local/bin/kafka-console-consumer --zookeeper localhost:2181 --from-beginning --topic my-replicated-topic

my test message 1
my test message 2

```

现在让我们测试一下容错性。Broker 1是其中的leader，让我们关了它：

```bash
ps | grep server-1.properties

7564 ttys002    0:15.91 /System/Library/Frameworks/JavaVM.framework/Versions/1.6/Home/bin/java...

kill -9 7564

```

Leader节点转移了，并且1号节点不再存在于“正在同步”的副本集合内：

```bash
/usr/local/bin/kafka-topics --describe --zookeeper localhost:218192 --topic my-replicated-topic

Topic:my-replicated-topic    PartitionCount:1    ReplicationFactor:3 Configs:

Topic: my-replicated-topic  Partition: 0    Leader: 2   Replicas: 1,2,0 Isr: 2,0

```
但是这些消息仍然可以用来消费，即便是原本负责写的leader节点被关掉了：

```bash
bin/kafka-console-consumer --zookeeper localhost:2181 --from-beginning --topic my-replicated-topic

my test message 1
my test message 2

```


# 安装kafka的php扩展 
```bash
brew install homebrew/php/php70-rdkafka

```
我们这里选取了 php70-rdkafka 这个扩展，安装后重启php-fpm，大功告